<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'dni',
        'sex',
        'birth_date',
        'phone',
        'address',
        'occupation',
    ];

    // Accesor conveniente
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getAgeFullAttribute(): ?string
    {
        if (!$this->birth_date) return null;
        $d = \Carbon\Carbon::parse($this->birth_date)->diff(\Carbon\Carbon::now());
        $text = $d->y . ' años';
        if ($d->m > 0) $text .= ' ' . $d->m . ' meses';
        return $text;
    }

    protected function dni(): Attribute
    {
        return Attribute::make(
        set: function ($value) {
            $digits = preg_replace('/\D/', '', (string) $value);
            // Guarda NULL si queda vacío (permite múltiples NULL con índice UNIQUE)
            return $digits !== '' ? $digits : null;
        },
        get: fn ($value) => ($value && strlen($value) === 13)
            ? substr($value, 0, 4) . '-' . substr($value, 4, 4) . '-' . substr($value, 8, 5)
            : $value,

        );
    }

    //relaciones
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function clinicalBackground()
    {
        return $this->hasOne(\App\Models\ClinicalBackground::class);
    }

    public function medicalHistories()
    {
        return $this->hasMany(\App\Models\MedicalHistory::class)->orderByDesc('visit_date');
    }

    public function prescriptions()
    {
        // útil para tab de recetas del paciente
        return $this->hasManyThrough(
            \App\Models\Prescription::class,
            \App\Models\MedicalHistory::class,
            'patient_id',        // FK en medical_histories -> patients.id
            'medical_history_id',// FK en prescriptions -> medical_histories.id
            'id',                // local key patients
            'id'                 // local key medical_histories
        )->latest('issued_at');
    }

    /** ---- Helpers ------------------------------------------------------ */
    public static function normalizeDigits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value);
    }

    public function getDisplayLabelAttribute(): string
    {
        $name  = $this->display_name;
        $dni   = $this->dni ? " ({$this->dni})" : '';
        $phone = $this->phone_for_display ? " · {$this->phone_for_display}" : '';
        return $name . $dni . $phone;
    }

      /** ---- Scopes para lookups/autocomplete ---------------------------- */

    public function scopeForLookup($q): void
    {
        $q->select('id', 'first_name', 'last_name', 'dni', 'phone')
          ->orderBy('last_name')
          ->orderBy('first_name');
    }

    public function scopeSearchTerm($q, string $term): void
    {
        $term   = trim($term);
        $digits = static::normalizeDigits($term);

        // Evita ruido con 1 solo carácter
        if (mb_strlen($term) < 2) {
            // truco: fuerza a no devolver filas
            $q->whereRaw('1=0');
            return;
        }

        $q->where(function ($w) use ($term, $digits) {
            // Texto (nombre/apellido)
            $w->where('first_name', 'like', "%{$term}%")
              ->orWhere('last_name',  'like', "%{$term}%")
              // DNI tal cual
              ->orWhere('dni', 'like', "%{$term}%");

            // Si hay números, aplicar normalizaciones
            if ($digits !== '') {
                // DNI sin guiones
                $w->orWhereRaw('REPLACE(dni, "-", "") like ?', ["%{$digits}%"]);
                // Teléfono tal cual
                $w->orWhere('phone', 'like', "%{$term}%");
                // Teléfono solo dígitos
                $w->orWhereRaw(
                    'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, "-", ""), " ", ""), "(", ""), ")", ""), "+", "") like ?',
                    ["%{$digits}%"]
                );
            }
        });
    }

    public function getDisplayNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getPhoneForDisplayAttribute(): ?string
    {
        // 1) Tel del paciente
        if (!empty($this->phone)) {
            return $this->phone;
        }

        // 2) Si tienes relación contacts() o guardian() en tu modelo, úsala:
        try {
            if (method_exists($this, 'contacts')) {
                $c = $this->contacts()->whereNotNull('phone')->first();
                if ($c?->phone) return $c->phone;
            }
            if (method_exists($this, 'guardian')) {
                $g = $this->guardian()->first();
                if ($g?->phone) return $g->phone;
            }
        } catch (\Throwable $e) {
            // Silencioso: si no existe la relación o falla, seguimos sin teléfono
        }

        return null;
    }

    public function getPrimaryPhoneAttribute(): ?string
    {
        if (!empty($this->phone)) return $this->phone;

        try {
            if (method_exists($this, 'contacts')) {
                $c = $this->contacts()->whereNotNull('phone')->first();
                if ($c?->phone) return $c->phone;
            }
            if (method_exists($this, 'guardian')) {
                $g = $this->guardian()->first();
                if ($g?->phone) return $g->phone;
            }
        } catch (\Throwable $e) {}
        return null;
    }

    protected function getPreferredContact(): ?\App\Models\Contact
    {
        try {
            // Si ya viene eager-loaded, usamos la colección; si no, la cargamos.
            $contacts = $this->relationLoaded('contacts') ? $this->contacts : $this->contacts()->get();
            if ($contacts->isEmpty()) return null;

            // Orden de prioridad por parentesco (en cualquier idioma común).
            $priorityMap = [
                'madre' => 0, 'mother' => 0,
                'padre' => 0, 'father' => 0,
                'encargado' => 1, 'guardian' => 1,
                'conyugue' => 2, 'cónyuge' => 2, 'spouse' => 2, 'husband' => 2, 'wife' => 2,
                'familiar' => 3, 'relative' => 3,
            ];

            return $contacts
                ->sortBy(function ($c) use ($priorityMap) {
                    $rel = strtolower(trim((string)($c->relation ?? $c->relationship ?? $c->kinship ?? '')));
                    $prio = $priorityMap[$rel] ?? 4;
                    // En igualdad de prioridad, preferimos el que tenga teléfono.
                    $hasPhone = !empty($c->phone) ? 0 : 1;
                    return [$prio, $hasPhone];
                })
                ->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Mapea el parentesco a etiqueta ES: Madre/Padre/Encargado/Cónyuge/Familiar. */
    protected function mapRelationToSpanish(?string $value): ?string
    {
        if (!$value) return null;
        $v = strtolower(trim($value));

        return match ($v) {
            'madre', 'mother' => 'Madre',
            'padre', 'father' => 'Padre',
            'encargado', 'guardian' => 'Encargado',
            'conyugue', 'cónyuge', 'spouse', 'husband', 'wife' => 'Cónyuge',
            'familiar', 'relative' => 'Familiar',
            default => ucfirst($value),
        };
    }

    public function getPrimaryContactNameAttribute(): ?string
    {
        $c = $this->getPreferredContact();
        if (!$c) return null;

        $name = trim(implode(' ', array_filter([$c->first_name ?? null, $c->last_name ?? null])));
        return $name !== '' ? $name : null;
    }

    public function getPrimaryContactPhoneAttribute(): ?string
    {
        $c = $this->getPreferredContact();
        return $c?->phone ?: null;
    }

    public function getPrimaryContactRelationAttribute(): ?string
    {
        $c = $this->getPreferredContact();
        return $this->mapRelationToSpanish($c?->relationship ?? null);
    }

    public function getGenderLabelAttribute(): ?string
    {
        return match ($this->sex) {
            'M'     => 'Masculino',
            'F'     => 'Femenino',
            'Other' => 'Otro',
            null    => null,
            default => ucfirst((string) $this->sex), // fallback por si hay otro valor
        };
    }

    public function attachments()
    {
        return $this->hasMany(\App\Models\Attachment::class);
    }

    public function attachmentBatches()
    {
        return $this->hasMany(\App\Models\AttachmentBatch::class);
    }
    
}

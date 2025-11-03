<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Attachment; 

class AttachmentPolicy
{
public function viewAny(User $user): bool
    {
        return $user->can('patient.attachments.viewAny');
    }

    // Ver un documento específico (preview inline)
    public function view(User $user, Attachment $attachment): bool
    {
        return $user->can('patient.attachments.view');
    }

    // Descargar (separado de "ver")
    public function download(User $user, Attachment $attachment): bool
    {
        return $user->can('patient.attachments.download');
    }

    public function create(User $user): bool
    {
        return $user->can('patient.attachments.create');
    }

    public function update(User $user, Attachment $attachment): bool
    {
        return $user->can('patient.attachments.update');
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        return $user->can('patient.attachments.delete');
    }
}

<?php

namespace Plugins\ImageX\Services;

use Fresns\DTO\DTO;

class LogicalDeletionFiles extends DTO
{
    public function rules(): array
    {
        return [
            'fileIdsOrFids' => ['array', 'required'],
        ];
    }
}

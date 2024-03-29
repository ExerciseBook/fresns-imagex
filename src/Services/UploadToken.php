<?php

namespace Plugins\ImageX\Services;

use Fresns\DTO\DTO;

class UploadToken extends DTO
{
    public function rules(): array
    {
        return [
            'type' => ['integer', 'required'],
            'count' => ['integer'],
        ];
    }
}

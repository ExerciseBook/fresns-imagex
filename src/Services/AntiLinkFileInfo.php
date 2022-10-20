<?php

namespace Plugins\ImageX\Services;

use App\Models\File;
use Fresns\DTO\DTO;
use Illuminate\Validation\Rule;

class AntiLinkFileInfo extends DTO
{
    public function rules(): array
    {
        return [
            'type' => ['integer', 'required', Rule::in(array_keys(File::TYPE_MAP))],
            'fileIdOrFid' => ['string', 'required'],
        ];
    }
}

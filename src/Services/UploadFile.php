<?php

namespace Plugins\ImageX\Services;

use Fresns\DTO\DTO;

class UploadFile extends DTO
{
    public function rules(): array
    {
        return [
            'platformId' => ['integer', 'required'],
            'usageType' => ['integer', 'required'],
            'tableName' => ['string', 'required'],
            'tableColumn' => ['string', 'required'],
            'tableId' => ['integer', 'nullable'],
            'tableKey' => ['string', 'nullable'],
            'aid' => ['string', 'nullable'],
            'uid' => ['integer', 'nullable'],
            'type' => ['integer', 'required'],
            'file' => ['file', 'required'],
            'moreInfo' => ['json', 'nullable'],
        ];
    }
}

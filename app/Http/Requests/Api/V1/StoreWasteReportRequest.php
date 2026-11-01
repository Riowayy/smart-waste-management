<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\StoreWasteReportRequest as BaseStoreWasteReportRequest;

class StoreWasteReportRequest extends BaseStoreWasteReportRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isUser() === true;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['user_id']);

        return $rules;
    }
}

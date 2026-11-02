<?php

namespace App\Http\Requests\User;

use App\Http\Requests\StoreWasteReportRequest as BaseStoreWasteReportRequest;

class StoreWasteReportRequest extends BaseStoreWasteReportRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isUser() === true;
    }

    public function rules(): array
    {
        return array_replace($this->reportRules(), [
            'description' => ['required', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);
    }

    public function attributes(): array
    {
        return array_replace(parent::attributes(), ['latitude' => 'Latitude', 'longitude' => 'Longitude']);
    }
}

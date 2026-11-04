<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\StoreWasteReportRequest as BaseStoreWasteReportRequest;

class StoreWasteReportRequest extends BaseStoreWasteReportRequest
{
    use NormalizesReportCoordinates;
}

<?php

namespace Modules\BillingBase\Providers;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Illuminate\Log\Writer
 * See: https://stackoverflow.com/questions/37809989/laravel-5-2-custom-log-file-for-different-tasks
 */
class CompanyData extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'companydata';
    }
}

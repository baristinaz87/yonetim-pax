<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OurServiceResource;
use App\Models\OurService;
use Illuminate\Http\Request;

class OurServiceController extends Controller
{
    /**
     * Get all services
     *
     */
    public function index()
    {
        $services = OurService::all();

        return OurServiceResource::collection($services);
    }

    /**
     * Get a specific service
     *
     * @param OurService $service
     * @return OurServiceResource
     */
    public function show(OurService $service): OurServiceResource
    {
        return new OurServiceResource($service);
    }
}

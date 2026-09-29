<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    /**
     * Get global conference configuration for the mobile app.
     */
    public function index()
    {
        return response()->json(config('conference'));
    }
}

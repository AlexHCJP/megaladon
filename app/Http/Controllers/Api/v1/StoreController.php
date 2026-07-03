<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Store\IndexStoreRequest;
use App\Http\Requests\Store\RateStoreRequest;
use App\Http\Requests\Store\UpdateStoreRequest;
use App\Http\Requests\Store\UploadFileRequest;
use App\Services\v1\StoreService;
use Illuminate\Http\Request;

class StoreController extends ApiController
{
    private StoreService $storeService;

    public function __construct() {
        $this->storeService = new StoreService();
    }

    public function types()
    {
        return $this->result($this->storeService->types());
    }

    public function updateProfile(UpdateStoreRequest $request)
    {
        $data = $request->validated();
        return $this->result($this->storeService->updateProfile($this->authUser(), $data));
    }

    public function index(IndexStoreRequest $request)
    {
        $params = $request->validated();
        return $this->result($this->storeService->index($params));
    }

    public function info($id)
    {
        return $this->result($this->storeService->info($id));
    }

    public function uploadPrice(UploadFileRequest $request)
    {
        $data = $request->validated();
        return $this->result($this->storeService->uploadPriceList($data));
    }

    public function activatePrice($id)
    {
        return $this->result($this->storeService->activatePrice($id));
    }

    public function deactivatePrice($id)
    {
        return $this->result($this->storeService->deactivatePrice($id));
    }

    public function deletePrice($id)
    {
        return $this->result($this->storeService->deletePrice($id));
    }

    public function rate(RateStoreRequest $request, $id)
    {
        $data = $request->validated();
        return $this->result($this->storeService->rateStore($id, $data));
    }

    public function ratings($id)
    {
        return $this->result($this->storeService->ratings($id));
    }
}

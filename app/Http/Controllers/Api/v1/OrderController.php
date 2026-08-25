<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Order\{
    CommentOrderRequest,
    CreateOrderRequest,
    IndexOrderRequest,
    RateOrderRequest,
    UpdateOrderRequest,
};
use App\Services\v1\OrderService;

class OrderController extends ApiController
{
    private OrderService $orderService;

    public function __construct() {
        $this->orderService = new OrderService();
    }

    public function store(CreateOrderRequest $request)
    {
        $data = $request->validated();
        return $this->result($this->orderService->create($this->authUser(), $data));
    }

    public function update($id, UpdateOrderRequest $request)
    {
        $data = $request->validated();
        return $this->result($this->orderService->update($id, $data));
    }

    public function index(IndexOrderRequest $request)
    {
        $params = $request->validated();
        return $this->result($this->orderService->index($params));
    }

    public function indexMy(IndexOrderRequest $request)
    {
        $params = $request->validated();
        return $this->result($this->orderService->indexMy($params));
    }

    public function indexMyResponded(IndexOrderRequest $request)
    {
        $params = $request->validated();
        return $this->result($this->orderService->indexMyResponded($params));
    }

    public function badges()
    {
        return $this->result($this->orderService->badges());
    }

    public function info($id)
    {
        return $this->result($this->orderService->info($id));
    }

    public function delete($id)
    {
        return $this->result($this->orderService->delete($id));
    }

    public function complete($id)
    {
        return $this->result($this->orderService->complete($id));
    }

    public function rate(RateOrderRequest $request, $id)
    {
        $data = $request->validated();
        return $this->result($this->orderService->rateExecutor($id, $data));
    }
}

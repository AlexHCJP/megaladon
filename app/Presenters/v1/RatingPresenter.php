<?php

namespace App\Presenters\v1;

use App\Presenters\BasePresenter;

class RatingPresenter extends BasePresenter
{
    public function list()
    {
        return [
            'id' => $this->id,
            'rate' => $this->rate,
            'comment' => $this->comment,
            'created_at' => $this->created_at ? strtotime($this->created_at) : null,
            'user' => [
                'id' => $this->user->id ?? null,
                'name' => $this->user->name ?? null,
                'photo_url' => ($this->user && $this->user->photo_url)
                    ? url($this->user->photo_url)
                    : null,
            ],
            'media' => $this->presentCollections($this->media, MediaFilePresenter::class, 'list'),
        ];
    }
}

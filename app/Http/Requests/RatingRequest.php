<?php

namespace App\Http\Requests;

use App\Models\Rating;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RatingRequest extends FormRequest
{
    public function authorize()
    {
        return backpack_auth()->check();
    }

    /**
     * Правила по оценке и тексту совпадают с API-реквестом RateStoreRequest,
     * чтобы админка не могла записать то, чего не примет приложение.
     *
     * Ограничение «один отзыв на объект от пользователя», которое есть в API,
     * здесь намеренно не воспроизводится: админ правит уже существующие
     * данные, и такой запрет мешал бы редактировать чужой отзыв.
     */
    public function rules()
    {
        return [
            'user_id' => 'required|exists:users,id',
            'ratingable_key' => 'required|string',
            'rate' => 'required|numeric|min:1.0|max:5.0',
            'comment' => 'nullable|string|max:2000',
        ];
    }

    /**
     * ratingable_key приходит из select'а склеенным: «App\Models\Executor|12».
     * Правилом exists это не выразить — таблица заранее не известна,
     * поэтому проверяем вручную: класс из белого списка, запись существует.
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {
            $key = $this->input('ratingable_key');

            if (!is_string($key) || $key === '') {
                return;
            }

            [$type, $id] = array_pad(explode('|', $key, 2), 2, null);

            if (!array_key_exists($type, Rating::RATINGABLE_TYPES) || !ctype_digit((string) $id)) {
                $validator->errors()->add('ratingable_key', 'Некорректный объект отзыва.');

                return;
            }

            if (!$type::query()->whereKey((int) $id)->exists()) {
                $validator->errors()->add('ratingable_key', 'Объект отзыва не найден.');
            }
        });
    }

    public function attributes()
    {
        return [
            'user_id' => 'автор',
            'ratingable_key' => 'объект отзыва',
            'rate' => 'оценка',
            'comment' => 'отзыв',
        ];
    }

    public function messages()
    {
        return [];
    }
}

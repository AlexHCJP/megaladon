<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\RatingRequest;
use App\Models\Executor;
use App\Models\Store;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class RatingCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class RatingCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Rating::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/rating');
        CRUD::setEntityNameStrings('отзыв', 'отзывы');
    }

    protected function setupListOperation()
    {
        // Без предзагрузки колонки «Автор», «Объект» и «Фото» дают N+1.
        // Автора берём вместе с удалёнными: User использует SoftDeletes,
        // а в админке имя ушедшего пользователя всё равно нужно видеть.
        $this->crud->query = $this->crud->query
            ->with([
                'user' => function ($query) {
                    $query->withTrashed();
                },
                'ratingable',
            ])
            ->withCount('media');

        $this->crud->orderBy('created_at', 'desc');

        CRUD::addColumn([
            'name' => 'id',
            'label' => 'ID',
            'type' => 'text',
        ]);
        // Точечную нотацию объявляем fluent-формой без явного type —
        // ровно как в ExecutorCrudController, чтобы Backpack сам
        // распознал связь. С 'type' => 'text' точка может не разрешиться.
        CRUD::column('user.name')->label('Автор');
        CRUD::addColumn([
            'name' => 'rate',
            'label' => 'Оценка',
            'type' => 'number',
            'decimals' => 1,
        ]);
        CRUD::addColumn([
            'name' => 'comment',
            'label' => 'Отзыв',
            'type' => 'text',
            'limit' => 60,
        ]);
        // ratingable_label и media_count — не колонки таблицы, поэтому
        // сортировку и участие в поиске отключаем: иначе Backpack
        // подставит их в ORDER BY / LIKE и запрос упадёт.
        CRUD::addColumn([
            'name' => 'ratingable_label',
            'label' => 'Объект',
            'type' => 'text',
            'orderable' => false,
            'searchLogic' => false,
        ]);
        CRUD::addColumn([
            'name' => 'media_count',
            'label' => 'Фото',
            'type' => 'text',
            'orderable' => false,
            'searchLogic' => false,
        ]);
        CRUD::addColumn([
            'name' => 'created_at',
            'label' => 'Дата',
            'type' => 'datetime',
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(RatingRequest::class);

        CRUD::addField([
            'label' => 'Автор',
            'type' => 'select',
            'name' => 'user_id',
            'entity' => 'user',
            'attribute' => 'name',
            'model' => \App\Models\User::class,
        ]);
        CRUD::addField([
            'label' => 'Объект отзыва',
            'name' => 'ratingable_key',
            'type' => 'select_from_array',
            'options' => $this->ratingableOptions(),
            'allows_null' => false,
        ]);
        CRUD::addField([
            'label' => 'Оценка',
            'name' => 'rate',
            'type' => 'number',
            'attributes' => [
                'step' => '0.5',
                'min' => '1',
                'max' => '5',
            ],
        ]);
        CRUD::addField([
            'label' => 'Отзыв',
            'name' => 'comment',
            'type' => 'textarea',
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * В проекте карточка просмотра переопределяется именно этим методом
     * (см. ExecutorCrudController) — он заменяет автоколонки из схемы базы.
     */
    protected function autoSetupShowOperation()
    {
        $this->crud->query = $this->crud->query->with([
            'user' => function ($query) {
                $query->withTrashed();
            },
            'ratingable',
            'media',
        ]);

        CRUD::addColumn([
            'name' => 'id',
            'label' => 'ID',
            'type' => 'text',
        ]);
        CRUD::column('user.name')->label('Автор');
        CRUD::addColumn([
            'name' => 'ratingable_label',
            'label' => 'Объект',
            'type' => 'text',
        ]);
        CRUD::addColumn([
            'name' => 'rate',
            'label' => 'Оценка',
            'type' => 'number',
            'decimals' => 1,
        ]);
        CRUD::addColumn([
            'name' => 'comment',
            'label' => 'Отзыв',
            'type' => 'text',
            'limit' => 3000,
        ]);
        CRUD::addColumn([
            'name' => 'media',
            'label' => 'Фотографии',
            'type' => 'rating_photos',
        ]);
        CRUD::addColumn([
            'name' => 'created_at',
            'label' => 'Создан',
            'type' => 'datetime',
        ]);
        CRUD::addColumn([
            'name' => 'updated_at',
            'label' => 'Изменён',
            'type' => 'datetime',
        ]);
    }

    /**
     * Варианты для поля «Объект отзыва»: связь полиморфная, а поле в форме
     * одно, поэтому ключ склеен из класса и идентификатора. Разбирает его
     * обратно мутатор Rating::setRatingableKeyAttribute().
     */
    private function ratingableOptions(): array
    {
        $options = [];

        foreach (Executor::orderBy('name')->get(['id', 'name']) as $executor) {
            $options[Executor::class . '|' . $executor->id] =
                'Исполнитель — ' . ($executor->name ?: '#' . $executor->id);
        }

        foreach (Store::orderBy('name')->get(['id', 'name']) as $store) {
            $options[Store::class . '|' . $store->id] =
                'Магазин — ' . ($store->name ?: '#' . $store->id);
        }

        return $options;
    }
}

{{--
    Фотографии отзыва. media — это morphMany-коллекция, а штатная колонка
    image умеет только один путь, поэтому рисуем миниатюры сами.
    storage_link хранится относительным (Storage::url), url() достраивает домен.
--}}
@php
    $photos = $entry->media ?? collect();
@endphp

<span>
    @forelse($photos as $photo)
        <a href="{{ url($photo->storage_link) }}" target="_blank" rel="noopener">
            <img
                src="{{ url($photo->storage_link) }}"
                alt="Фото отзыва"
                style="height: 80px; width: 80px; object-fit: cover; border-radius: 4px; margin: 0 4px 4px 0;"
            >
        </a>
    @empty
        -
    @endforelse
</span>

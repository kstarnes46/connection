<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;

/**
 * \App\Models\Entry
 * @property int $id
 * @property string $content_id
 * @property string $collection_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \App\Models\Content $content
 * @property \App\Models\PostCollection $collection
 */
class Entry extends Model
{
    use AsPivot;

    /**
     * The attributes that are mass assignable.
     * @var array<int, string>
     */
    protected $fillable = ["content_id", "collection_id"];

    /**
     * The attributes that should be cast.
     * @var array<string, string>
     */
    protected $casts = [
        "created_at" => "datetime",
        "updated_at" => "datetime",
    ];

    // Relationships

    /**
     * Get the content that the entry belongs to.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Content>
     */
    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    /**
     * Get the collection that the entry belongs to.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<PostCollection>
     */
    public function collection()
    {
        return $this->belongsTo(PostCollection::class);
    }
}

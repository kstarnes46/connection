<?php

namespace App\Models;

use App\Notifications\QuestionAnswered;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * |=============================================================================|
 * | FrequentlyAskedQuestion                                                     |
 * |-----------------------------------------------------------------------------|
 * | This model represents a frequently asked question.                          |
 * |=============================================================================|
 * @property int $id
 * @property string $title
 * @property string $content
 * @property string|null $user_id
 * @property \Illuminate\Support\Carbon|null $answered_at
 * @property string|null $answer
 * @property-read bool $is_answered
 * @property-read float $rating
 * @property-read string $content_excerpt
 * @property-read string $answer_excerpt
 * @property \Illuminate\Database\Eloquent\Collection $history
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \App\Models\User|null $user
 */
class FrequentlyAskedQuestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ["title", "content", "user_id"];

    protected $casts = [
        "history" => "collection",
        "answered_at" => "datetime",
        "created_at" => "datetime",
        "updated_at" => "datetime",
    ];

    protected $attributes = [
        "history" => "[]",
        "content" => "",
    ];

    /**
     * Get the user that asked the question.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include answered questions.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAnswered($query)
    {
        return $query->whereNotNull("answer");
    }

    /**
     * Scope a query to only include unanswered questions.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnanswered($query)
    {
        return $query->whereNull("answer");
    }

    /**
     * Get the overall rating of the question.
     */
    public function getRatingAttribute()
    {
        $rating = $this->history->reduce(function (int $carry, array $item) {
            if ($item["action"] === "upvote") {
                return $carry + 1;
            }
            if ($item["action"] === "downvote") {
                return $carry - 1;
            }
        }, 0);
        if ($rating > 0) {
            $rating = ($rating / $this->history->count()) * 100;
        }
        return (float) $rating;
    }

    public function getIsAnsweredAttribute()
    {
        return !is_null($this->answer);
    }

    public function getContentExcerptAttribute()
    {
        return Str::limit($this->content, 20);
    }

    public function getAnswerExcerptAttribute()
    {
        return Str::limit($this->answer, 20);
    }

    protected static function booted()
    {
        static::saving(function (FrequentlyAskedQuestion $question) {
            if ($question->isDirty("answer")) {
                $question->answered_at = now();
            }
        });

        static::saved(function (FrequentlyAskedQuestion $question) {
            if ($question->wasChanged("answer")) {
                if (!is_null($question->user)) {
                    $question->user->notify(new QuestionAnswered($question));
                }
            }
        });
    }

    public function getRouteKey()
    {
        return Str::slug($this->title) .
            "-" .
            $this->getAttribute($this->getRouteKeyName());
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $id = last(explode("-", $value));
        return parent::resolveRouteBinding($id, $field);
    }

    public function upvote()
    {
        $this->history->add([
            "action" => "upvote",
            "user_id" => auth()->id(),
            "timestamp" => now(),
        ]);
    }

    public function downvote()
    {
        $this->history->add([
            "action" => "downvote",
            "user_id" => auth()->id(),
            "timestamp" => now(),
        ]);
    }
}

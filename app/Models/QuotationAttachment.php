<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationAttachment extends Model
{
    use HasFactory;

    /** Extensions kept on stored files; anything else is stored as .bin (never executable or rendered as a page). */
    public const SAFE_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'txt', 'rtf',
        'jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'dwg', 'dxf', 'zip', 'rar', '7z',
    ];

    protected $fillable = [
        'quotation_id',
        'file_name',
        'file_path',
        'file_size',
    ];

    /** Paths the builder upload creates: quotation_attachments/<40 random chars>.<safe ext>. */
    public static function isUploadPath(mixed $path): bool
    {
        return is_string($path)
            && preg_match('#^quotation_attachments/[A-Za-z0-9]{40}\.(' . implode('|', array_merge(self::SAFE_EXTENSIONS, ['bin'])) . ')$#', $path) === 1;
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}

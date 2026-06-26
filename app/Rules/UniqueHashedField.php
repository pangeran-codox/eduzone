<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Validasi uniqueness untuk field yang disimpan sebagai hash (bukan plain),
 * misal nisn_hash, nis_hash di tabel students.
 *
 * Penggunaan:
 *   'nisn' => ['required', new UniqueHashedField('students', 'nisn_hash')],
 *
 * Untuk update (exclude ID sendiri):
 *   'nisn' => ['required', (new UniqueHashedField('students', 'nisn_hash'))->ignore($student->id)],
 */
class UniqueHashedField implements ValidationRule
{
    protected ?string $ignoreId = null;

    protected string $idColumn = 'id';

    public function __construct(
        protected string $table,
        protected string $hashColumn,
    ) {
    }

    public function ignore(string $id, string $idColumn = 'id'): static
    {
        $this->ignoreId = $id;
        $this->idColumn = $idColumn;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $hash = hash('sha256', $value);

        $query = DB::table($this->table)->where($this->hashColumn, $hash);

        if ($this->ignoreId !== null) {
            $query->where($this->idColumn, '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fail("Data {$attribute} sudah terdaftar.");
        }
    }
}
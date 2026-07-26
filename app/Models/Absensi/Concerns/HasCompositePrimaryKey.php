<?php

namespace App\Models\Absensi\Concerns;

/**
 * Dukungan composite primary key untuk model yang PK-nya lebih dari 1 kolom.
 * Saat ini cuma dipakai PeopleRef (person_id + person_type) - Eloquent tidak
 * native support composite PK, jadi method inti ini di-override manual.
 *
 * KETERBATASAN PENTING:
 * - Model::find($id) TIDAK bisa dipakai (butuh 1 nilai scalar). Query composite
 *   key pakai where(), contoh:
 *   PeopleRef::where('person_id', $id)->where('person_type', $type)->first();
 * - $model->save() untuk UPDATE tetap jalan normal (dipanggil setelah fetch
 *   via where() di atas), karena method di bawah ini yang menyusun ulang
 *   klausa WHERE saat query save/update dijalankan.
 */
trait HasCompositePrimaryKey
{
    public function getKeyName()
    {
        return $this->primaryKey; // array, mis. ['person_id', 'person_type']
    }

    public function getKey()
    {
        $keys = [];
        foreach ((array) $this->getKeyName() as $key) {
            $keys[$key] = $this->getAttribute($key);
        }

        return $keys;
    }

    protected function setKeysForSaveQuery($query)
    {
        foreach ((array) $this->getKeyName() as $key) {
            $query->where($key, '=', $this->getKeyForSaveQuery($key));
        }

        return $query;
    }

    protected function getKeyForSaveQuery($keyName = null)
    {
        $keyName ??= $this->getKeyName()[0];

        return $this->original[$keyName] ?? $this->getAttribute($keyName);
    }
}

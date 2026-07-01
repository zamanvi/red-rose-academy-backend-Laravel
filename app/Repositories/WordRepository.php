<?php

namespace App\Repositories;

use App\Models\Word;

class WordRepository  implements WordRepositoryInterface
{
    protected $word;

    public function __construct(Word $word)
    {
        $this->word = $word;
    }

    public function getAll()
    {
        return $this->word->paginate(10);
    }
    public function getAllById($id)
    {
        return $this->word->with('lesson')->where('lesson_id', $id)->paginate(30);
    }

    public function findById($id)
    {
        return $this->word->findOrFail($id);
    }

    public function store(array $data)
    {
        return $this->word->create($data);
    }

    public function update($id, array $data)
    {
        return $this->findById($id)->update($data);
    }

    public function delete(int $id): bool
    {
        return $this->findById($id)->delete();
    }
}

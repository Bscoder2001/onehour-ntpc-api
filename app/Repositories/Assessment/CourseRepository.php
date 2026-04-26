<?php

namespace App\Repositories\Assessment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CourseRepository
{
    private function filterPayload($payload)
    {
        $columns = Schema::getColumnListing('courses');
        $filtered = [];

        foreach ($payload as $key => $value)
        {
            if (in_array($key, $columns, true))
            {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    public function create($payload)
    {
        return (int) DB::table('courses')->insertGetId($this->filterPayload($payload));
    }

    public function listAll()
    {
        $query = DB::table('courses')->select('id', 'name');

        if (Schema::hasColumn('courses', 'status'))
        {
            $query->addSelect('status')->orderByDesc('id');
        }
        else
        {
            $query->selectRaw("'active' as status")->orderByDesc('id');
        }

        return $query->get();
    }

    public function findById($id)
    {
        return DB::table('courses')->where('id', $id)->first();
    }

    public function update($id, $payload)
    {
        return DB::table('courses')->where('id', $id)->update($this->filterPayload($payload));
    }

    public function deactivateOrDelete($id)
    {
        if (Schema::hasColumn('courses', 'status'))
        {
            return DB::table('courses')->where('id', $id)->update([
                'status' => 0,
            ]);
        }

        return DB::table('courses')->where('id', $id)->delete();
    }
}

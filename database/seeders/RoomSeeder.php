<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
        public function run(): void
    {
        $rooms = [
            ['name' => 'LAB 1', 'capacity' => 40, 'type' => 'Lab'],
            ['name' => 'LAB 2', 'capacity' => 40, 'type' => 'Lab'],
            ['name' => 'ROOM 101', 'capacity' => 50, 'type' => 'Lecture'],
            ['name' => 'ROOM 102', 'capacity' => 50, 'type' => 'Lecture'],
        ];

        foreach ($rooms as $room) {
            \App\Models\Room::create($room);
        }
    }
}

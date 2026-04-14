<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Setting;
use App\Models\Schedule;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

class GlobalSettings extends Component
{
    public $start_time, $end_time, $semester_name, $default_duration;
    public $new_dept_name, $new_dept_code;
    public $confirmingReset = false;

    public function mount()
    {
        $this->start_time = Setting::where('key', 'start_time')->first()?->value ?? '07:00';
        $this->end_time = Setting::where('key', 'end_time')->first()?->value ?? '17:00';
        $this->semester_name = Setting::where('key', 'semester_name')->first()?->value ?? 'First Semester 2026-2027';
        $this->default_duration = Setting::where('key', 'default_duration')->first()?->value ?? '1.0';
    }

    public function save()
    {
        Setting::updateOrCreate(['key' => 'start_time'], ['value' => $this->start_time]);
        Setting::updateOrCreate(['key' => 'end_time'], ['value' => $this->end_time]);
        Setting::updateOrCreate(['key' => 'semester_name'], ['value' => $this->semester_name]);
        Setting::updateOrCreate(['key' => 'default_duration'], ['value' => $this->default_duration]);

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Settings updated successfully!']);
    }

    public function addDepartment()
    {
        $this->validate([
            'new_dept_name' => 'required|unique:departments,name',
            'new_dept_code' => 'required|unique:departments,code',
        ]);

        Department::create([
            'name' => $this->new_dept_name,
            'code' => strtoupper($this->new_dept_code),
        ]);

        $this->reset(['new_dept_name', 'new_dept_code']);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Department added!']);
    }

    public function deleteDepartment($id)
    {
        Department::find($id)->delete();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Department removed.']);
    }

    public function archiveAndReset()
    {
        $currentSchedules = Schedule::all();

        if ($currentSchedules->isEmpty()) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'No schedules found to archive!']);
            $this->confirmingReset = false;
            return;
        }

        DB::table('schedule_archives')->insert([
            'semester_name' => $this->semester_name,
            'schedule_data' => $currentSchedules->toJson(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schedule::query()->delete();

        $this->confirmingReset = false;
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Semester archived and grid cleared!']);
    }

    public function render()
    {
        return view('livewire.global-settings', [
            'departments' => Department::latest()->get(),
            'archives' => DB::table('schedule_archives')->latest()->get(),
        ])->layout('layouts.app');
    }
}
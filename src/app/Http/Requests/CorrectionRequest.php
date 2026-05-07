<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class CorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i', 'after:clock_in'],
            'remarks' => ['required', 'string', 'max:500'],
            'rest_mod.*.id' => ['required', 'exists:rests,id'],
            'rest_mod.*.start' => ['nullable', 'date_format:H:i'],
            'rest_mod.*.end' => ['nullable', 'date_format:H:i'],
            'rest_add.*.start' => ['nullable', 'date_format:H:i'],
            'rest_add.*.end' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'remarks.required' => '備考を記入してください',
            'clock_in.required' => '出勤時間を入力してください',
            'clock_out.required' => '退勤時間を入力してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();
            $clockIn = null;
            $clockOut = null;
            try {
                if (! empty($data['clock_in'])) {
                    $clockIn = Carbon::createFromFormat('H:i', $data['clock_in']);
                }
                if (! empty($data['clock_out'])) {
                    $clockOut = Carbon::createFromFormat('H:i', $data['clock_out']);
                }
            } catch (\Exception $e) {
                return;
            }

            if (! $clockIn || ! $clockOut) {
                return;
            }

            $checkRest = function ($startStr, $endStr, $baseKey) use ($validator, $clockIn, $clockOut) {
                if (empty($startStr) || empty($endStr)) {
                    return;
                }

                try {
                    $restStart = Carbon::createFromFormat('H:i', $startStr);
                    $restEnd = Carbon::createFromFormat('H:i', $endStr);
                } catch (\Exception $e) {
                    return;
                }

                if ($restStart->lt($clockIn) || $restStart->gt($clockOut)) {
                    $validator->errors()->add($baseKey.'.start', '休憩時間が不適切な値です');
                }

                if ($restEnd->gt($clockOut)) {
                    $validator->errors()->add($baseKey.'.end', '休憩時間もしくは退勤時間が不適切な値です');
                }

                if ($restEnd->lt($restStart)) {
                    $validator->errors()->add($baseKey.'.end', '休憩時間もしくは退勤時間が不適切な値です');
                }
            };

            if (isset($data['rest_mod']) && is_array($data['rest_mod'])) {
                foreach ($data['rest_mod'] as $id => $times) {
                    $checkRest($times['start'] ?? null, $times['end'] ?? null, "rest_mod.{$id}");
                }
            }

            if (isset($data['rest_add']) && is_array($data['rest_add'])) {
                foreach ($data['rest_add'] as $index => $times) {
                    $checkRest($times['start'] ?? null, $times['end'] ?? null, "rest_add.{$index}");
                }
            }
        });
    }
}

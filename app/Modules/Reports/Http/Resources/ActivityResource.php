<?php

namespace App\Modules\Reports\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'log_name' => $this->log_name,
            'subject_label' => $this->resolveSubjectLabel(),
            'description' => $this->description,
            'event' => $this->event,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'subject_uuid' => $this->resolveSubjectUuid(),
            'causer_type' => $this->causer_type,
            'causer_id' => $this->causer_id,
            'causer_name' => $this->resolveCauserName(),
            'causer_uuid' => $this->resolveCauserUuid(),
            'properties' => $this->filterProperties(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    protected function resolveCauserName(): ?string
    {
        try {
            return optional($this->causer)->name;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveCauserUuid(): ?string
    {
        try {
            $causer = $this->causer;
            if ($causer === null) {
                return null;
            }

            return isset($causer->uuid) ? (string) $causer->uuid : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveSubjectUuid(): ?string
    {
        try {
            $subject = $this->subject;
            if ($subject !== null && isset($subject->uuid) && (string) $subject->uuid !== '') {
                return (string) $subject->uuid;
            }
        } catch (\Throwable) {
            // Subject class missing or the record was removed.
        }

        $props = $this->properties;
        if (! $props) {
            return null;
        }

        $data = method_exists($props, 'toArray') ? $props->toArray() : (array) $props;
        $attrs = is_array($data['attributes'] ?? null) ? $data['attributes'] : [];
        $old = is_array($data['old'] ?? null) ? $data['old'] : [];

        foreach ([$attrs['uuid'] ?? null, $old['uuid'] ?? null, $data['subject_uuid'] ?? null] as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    protected function resolveSubjectLabel(): ?string
    {
        $props = $this->properties;
        if (! $props) {
            return null;
        }

        $data = method_exists($props, 'toArray') ? $props->toArray() : (array) $props;

        $direct = $data['subject_label'] ?? null;
        if (is_string($direct) && $direct !== '') {
            return $direct;
        }

        $attrs = is_array($data['attributes'] ?? null) ? $data['attributes'] : [];
        $old = is_array($data['old'] ?? null) ? $data['old'] : [];

        foreach (['name', 'label', 'title'] as $key) {
            foreach ([$attrs[$key] ?? null, $old[$key] ?? null] as $value) {
                if (is_string($value) && $value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    protected function filterProperties(): mixed
    {
        $props = $this->properties;
        if (! $props) {
            return $props;
        }

        $data = method_exists($props, 'toArray') ? $props->toArray() : (array) $props;

        unset($data['attributes']['password'], $data['old']['password']);

        return $data;
    }
}

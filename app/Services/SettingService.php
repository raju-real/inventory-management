<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SettingService
{
    public function __construct(private ImageService $images)
    {
    }

    public function save(array $data): void
    {
        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                if ($value instanceof UploadedFile) {
                    $this->images->delete(setting($key));
                    $value = $this->images->store($value, 'settings');
                }

                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }, 3);
    }

    public function saveMail(array $data): void
    {
        // An empty password field means "keep the saved password".
        if (empty($data['mail_password'])) {
            unset($data['mail_password']);
        } else {
            $data['mail_password'] = Crypt::encryptString($data['mail_password']);
        }

        $this->save($data);
    }
}

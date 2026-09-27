<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Support\HtmlString;

/**
 * Supplier (`os_vendor`).
 *
 * @property int $id
 * @property string $title
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $mobile
 * @property string|null $address
 */
#[Table('vendor', timestamps: false)]
#[Fillable(['title', 'email', 'phone', 'mobile', 'address'])]
class Vendor extends LegacyModel
{
    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'title' => 'Vendor', 'email' => 'Email', 'phone' => 'Phone', 'mobile' => 'Mobile', 'address' => 'Address'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'title' => ['required', 'max:255'],
            'email' => ['max:150'],
            'phone' => ['max:100'],
            'mobile' => ['max:100'],
            'address' => ['max:255'],
        ];
    }

    /**
     * Name, address and contacts for printed documents (Vendor::get_address_details()).
     */
    public function addressDetails(): HtmlString
    {
        $html = $this->title ? '<h4>'.e($this->title).'</h4>' : '';
        $html .= '<address>';
        $html .= $this->address ? e($this->address).'<br>' : '';
        $html .= $this->email ? '<abbr title="Email">E: </abbr>'.e($this->email).'<br>' : '';
        $html .= $this->phone ? '<abbr title="Phone">P: </abbr>'.e($this->phone).'<br>' : '';
        $html .= $this->mobile ? '<abbr title="Mobile">M: </abbr>'.e($this->mobile).'<br>' : '';

        return new HtmlString($html.'</address>');
    }
}

<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
/**
 * App\AddressBookGroup
 */
class AddressBookGroup extends Model {

    protected $guarded = ['id'];

    public function childs() {
        return $this->hasMany('App\AddressBookGroup', 'parent_groupid', 'id');
    }

}
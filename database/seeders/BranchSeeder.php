<?php
namespace Database\\Seeders;
use App\\Models\\Branch;
use Illuminate\\Database\\Seeder;
class BranchSeeder extends Seeder {
 public function run(): void {Branch::query()->updateOrCreate(['code'=>'fatoosh-al-khaldiya'],['name'=>'فتوش الخالدية','timezone'=>'Asia/Kuwait','is_active'=>true,'is_default'=>true]);}
}

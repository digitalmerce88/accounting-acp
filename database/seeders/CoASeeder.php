<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{Business,Account};
class CoASeeder extends Seeder {
    public function run(): void {
        $biz = Business::firstOrCreate(['name'=>'Demo Co., Ltd.'], ['country'=>'TH']);
        $accs = [
            ['101','เงินสด','asset'], ['102','เงินฝากธนาคาร','asset'], ['120','ลูกหนี้การค้า','asset'],
            ['150','อุปกรณ์','asset'], ['201','เจ้าหนี้การค้า','liability'], ['301','ทุน','equity'],
            ['401','รายได้จากการขาย','income'], ['402','รายได้จากการให้บริการ','income'],
            ['501','ต้นทุนขาย','expense'], ['502','ค่าใช้จ่ายทั่วไป','expense'], ['511','ภาษีซื้อ','asset'], ['411','ภาษีขาย','liability'],
        ];
        foreach($accs as $a){
            Account::updateOrCreate(['business_id'=>$biz->id,'code'=>$a[0]], ['name'=>$a[1],'type'=>$a[2]]);
        }
    }
}

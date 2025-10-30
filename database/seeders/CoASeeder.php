<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{Business,Account};
class CoASeeder extends Seeder {
    public function run(): void {
        $biz = Business::firstOrCreate(['name'=>'Demo Co., Ltd.'], ['country'=>'TH']);
        $accs = [
            // code, name, type, normal_balance
            ['101','เงินสด','asset','debit'],
            ['102','เงินฝากธนาคาร','asset','debit'],
            ['120','ลูกหนี้การค้า','asset','debit'],
            ['150','อุปกรณ์','asset','debit'],
            ['153','ภาษีหัก ณ ที่จ่ายรอเรียกคืน','asset','debit'],
            ['201','เจ้าหนี้การค้า','liability','credit'],
            ['231','ภาษีหัก ณ ที่จ่ายค้างจ่าย','liability','credit'],
            ['301','ทุน','equity','credit'],
            ['401','รายได้จากการขาย','revenue','credit'],
            ['402','รายได้จากการให้บริการ','revenue','credit'],
            ['501','ต้นทุนขาย','expense','debit'],
            ['502','ค่าใช้จ่ายทั่วไป','expense','debit'],
            ['511','ภาษีซื้อ','asset','debit'],
            ['411','ภาษีขาย','liability','credit'],
        ];
        foreach($accs as $a){
            Account::updateOrCreate(
                ['business_id'=>$biz->id,'code'=>$a[0]],
                ['name'=>$a[1],'type'=>$a[2],'normal_balance'=>$a[3]]
            );
        }
    }
}

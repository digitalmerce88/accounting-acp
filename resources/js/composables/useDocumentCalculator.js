import { computed } from 'vue'

export function useDocumentCalculator(form){
  const subtotal = computed(()=> (form.items||[]).reduce((s,it)=> s + (Number(it?.qty_decimal||0)*Number(it?.unit_price_decimal||0)), 0))

  const discount_amount = computed(()=>{
    const t = form.discount_type || 'none'
    const v = Number(form.discount_value_decimal||0)
    if(t==='percent') return subtotal.value * (Math.min(Math.max(v,0),100)/100)
    if(t==='amount') return Math.min(Math.max(v,0), subtotal.value)
    return 0
  })

  const adjusted_subtotal = computed(()=> subtotal.value - discount_amount.value)

  const vat = computed(()=>{
    if(subtotal.value <= 0) return 0
    const raw = (form.items||[]).reduce((s,it)=> s + (Number(it?.qty_decimal||0)*Number(it?.unit_price_decimal||0)) * (Number(it?.vat_rate_decimal||0)/100), 0)
    return raw * (adjusted_subtotal.value / (subtotal.value || 1))
  })

  const total = computed(()=> adjusted_subtotal.value + vat.value)

  const deposit_amount = computed(()=>{
    const t = form.deposit_type || 'none'
    const v = Number(form.deposit_value_decimal||0)
    if(t==='percent') return total.value * (Math.min(Math.max(v,0),100)/100)
    if(t==='amount') return Math.min(Math.max(v,0), total.value)
    return 0
  })

  const amount_due = computed(()=> total.value - deposit_amount.value)

  return {
    subtotal,
    discount_amount,
    adjusted_subtotal,
    vat,
    total,
    deposit_amount,
    amount_due,
  }
}

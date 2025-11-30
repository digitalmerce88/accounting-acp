export function statusLabel(s){
  if(!s) return '-'
  const map = {
    draft: 'ฉบับร่าง',
    submitted: 'ส่งอนุมัติ',
    approved: 'อนุมัติ',
    locked: 'ล็อก',
    unlocked: 'ปลดล็อกแล้ว',
    paid: 'ชำระแล้ว',
    unpaid: 'ยังไม่ชำระ',
    void: 'ยกเลิก',
    processed: 'ประมวลผลแล้ว',
    posted: 'บันทึกแล้ว',
    active: 'กำลังใช้งาน',
    inactive: 'ปิดใช้งาน',
    disposed: 'จำหน่าย',
  }
  return map[s] ?? s
}

export function approvalLabel(s){
  if(!s) return '-'
  const map = {
    draft: 'ฉบับร่าง',
    submitted: 'ส่งอนุมัติ',
    approved: 'อนุมัติ',
    locked: 'ล็อก',
  }
  return map[s] ?? s
}

export function flashLabel(s){
  if(!s) return s
  const map = {
    'verification-link-sent': 'ส่งลิงก์ยืนยันอีเมลแล้ว',
    'passwords.sent': 'ส่งอีเมลรีเซ็ตรหัสผ่านแล้ว',
  }
  return map[s] ?? s
}

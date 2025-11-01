import Swal from 'sweetalert2'

export function confirmDialog(message, options = {}){
  return Swal.fire({
    title: message,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: options.confirmButtonText || 'ยืนยัน',
    cancelButtonText: options.cancelButtonText || 'ยกเลิก',
    confirmButtonColor: options.confirmButtonColor || '#2563eb',
    cancelButtonColor: options.cancelButtonColor || '#6b7280',
    reverseButtons: true,
  }).then(r => r.isConfirmed)
}

export function alertSuccess(message, options = {}){
  return Swal.fire({
    icon: 'success',
    title: options.title || 'สำเร็จ',
    text: message,
    confirmButtonText: options.confirmButtonText || 'ปิด',
  })
}

export function alertInfo(message, options = {}){
  return Swal.fire({
    icon: 'info',
    title: options.title || 'แจ้งเตือน',
    text: message,
    confirmButtonText: options.confirmButtonText || 'ปิด',
  })
}

export function alertError(message, options = {}){
  return Swal.fire({
    icon: 'error',
    title: options.title || 'เกิดข้อผิดพลาด',
    text: message,
    confirmButtonText: options.confirmButtonText || 'ปิด',
  })
}

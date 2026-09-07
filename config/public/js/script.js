// Автоматическое скрытие уведомлений
document.addEventListener('DOMContentLoaded', function() {
    // Автоматическое закрытие alert через 5 секунд
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.click();
            }
        }, 5000);
    });
    
    // Валидация дат
    const checkInInput = document.querySelector('input[name="check_in"]');
    const checkOutInput = document.querySelector('input[name="check_out"]');
    
    if (checkInInput && checkOutInput) {
        checkInInput.addEventListener('change', function() {
            const checkIn = new Date(this.value);
            const minCheckOut = new Date(checkIn);
            minCheckOut.setDate(minCheckOut.getDate() + 1);
            
            const minDate = minCheckOut.toISOString().split('T')[0];
            checkOutInput.setAttribute('min', minDate);
            
            if (checkOutInput.value && new Date(checkOutInput.value) <= checkIn) {
                checkOutInput.value = '';
            }
        });
    }
    
    // Подтверждение действий
    document.querySelectorAll('[data-confirm]').forEach(element => {
        element.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Вы уверены?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // Авто-заполнение дат при поиске
    const searchForm = document.querySelector('form[action*="rooms"]');
    if (searchForm) {
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        const checkInField = searchForm.querySelector('input[name="check_in"]');
        const checkOutField = searchForm.querySelector('input[name="check_out"]');
        
        if (checkInField && !checkInField.value) {
            checkInField.value = today.toISOString().split('T')[0];
        }
        if (checkOutField && !checkOutField.value) {
            checkOutField.value = tomorrow.toISOString().split('T')[0];
        }
    }
});

// Функция для форматирования цены
function formatPrice(price) {
    return new Intl.NumberFormat('ru-RU').format(price) + ' руб.';
}

// Функция для подсчета ночей
function calculateNights(checkIn, checkOut) {
    const start = new Date(checkIn);
    const end = new Date(checkOut);
    const diffTime = Math.abs(end - start);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

// Логирование действий пользователя
function logUserAction(action, data = null) {
    console.log(`[${new Date().toISOString()}] ${action}`, data);
}

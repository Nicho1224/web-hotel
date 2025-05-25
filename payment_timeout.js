document.addEventListener('DOMContentLoaded', () => {
    const countdownElements = document.querySelectorAll('.payment-timer');
    
    countdownElements.forEach(element => {
        const transactionId = element.dataset.transactionId;
        let seconds = 600; // 10 menit

        const timer = setInterval(() => {
            seconds--;
            element.innerHTML = `
                <i class="bi bi-clock"></i> 
                ${Math.floor(seconds/60)}:${('0'+(seconds%60)).slice(-2)}
            `;

            if (seconds <= 0) {
                clearInterval(timer);
                cancelTransaction(transactionId, element);
            }
        }, 1000);
    });

    async function cancelTransaction(transactionId, element) {
        try {
            const response = await fetch('cancel_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `invoice_id=${transactionId}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                element.closest('tr').querySelector('.transaction-status').innerHTML = `
                    <span class="badge bg-danger">
                        <i class="bi bi-x-circle"></i> Dibatalkan
                    </span>
                `;
                element.remove();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
});
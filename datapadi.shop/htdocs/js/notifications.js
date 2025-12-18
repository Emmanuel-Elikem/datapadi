// Browser Push Notifications System (FREE!)
class DataPadiNotifications {
    constructor() {
        this.orderId = null;
        this.checkInterval = null;
        this.notificationTimes = {
            30: false,  // 30 minutes
            60: false,  // 1 hour
            120: false  // 2 hours
        };
    }

    // Initialize notifications
    async init(orderId) {
        this.orderId = orderId;
        
        // Request permission
        if ('Notification' in window) {
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                this.startMonitoring();
            }
        }
        
        // Also use localStorage for persistent notifications
        this.setupLocalNotifications();
    }

    // Start monitoring order
    startMonitoring() {
        const startTime = Date.now();
        
        this.checkInterval = setInterval(() => {
            const elapsed = (Date.now() - startTime) / 60000; // minutes
            
            // Check notification triggers
            if (elapsed >= 30 && !this.notificationTimes[30]) {
                this.sendNotification(30);
                this.notificationTimes[30] = true;
            }
            
            if (elapsed >= 60 && !this.notificationTimes[60]) {
                this.sendNotification(60);
                this.notificationTimes[60] = true;
            }
            
            if (elapsed >= 120 && !this.notificationTimes[120]) {
                this.sendNotification(120);
                this.notificationTimes[120] = true;
            }
            
            // Check order status
            this.checkOrderStatus();
            
        }, 30000); // Check every 30 seconds
    }

    // Send notification based on time
    async sendNotification(minutes) {
        let title, body, icon;
        
        switch(minutes) {
            case 30:
                title = "Order Update";
                body = "Your data order is being processed. It typically takes 20-60 minutes for delivery.";
                icon = "⏳";
                break;
            case 60:
                title = "Still Processing";
                body = "We're working on your order. If there's any delay, we'll update you shortly.";
                icon = "🔄";
                break;
            case 120:
                title = "Extended Processing";
                body = "Your order is taking longer than usual. Please check the app for updates or contact support.";
                icon = "⚠️";
                break;
        }
        
        // Browser notification
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(title, {
                body: body,
                icon: '/images/logo.png',
                badge: '/images/badge.png',
                vibrate: [200, 100, 200],
                tag: 'order-' + this.orderId
            });
        }
        
        // Also show in-page notification
        this.showInPageNotification(title, body, icon);
    }

    // Check order status
    async checkOrderStatus() {
        try {
            const response = await fetch(`/api/check-order-status.php?id=${this.orderId}`);
            const data = await response.json();
            
            if (data.success && data.order) {
                const order = data.order;
                
                // Update UI
                this.updateOrderUI(order);
                
                // Check for completion
                if (order.status === 'completed') {
                    this.orderCompleted();
                } else if (order.status === 'failed') {
                    this.orderFailed();
                }
            }
        } catch (error) {
            console.error('Error checking status:', error);
        }
    }

    // Update order UI
    updateOrderUI(order) {
        const statusElement = document.getElementById('orderStatus');
        if (statusElement) {
            statusElement.innerHTML = `
                <div class="status-card ${order.status}">
                    <div class="status-icon">${this.getStatusIcon(order.status)}</div>
                    <div class="status-text">
                        <h3>Order ${order.status.toUpperCase()}</h3>
                        <p>Order ID: ${order.order_id}</p>
                        <p>Updated: ${new Date().toLocaleTimeString()}</p>
                    </div>
                </div>
            `;
        }
    }

    // Get status icon
    getStatusIcon(status) {
        const icons = {
            'pending': '⏳',
            'processing': '🔄',
            'completed': '✅',
            'failed': '❌'
        };
        return icons[status] || '📦';
    }

    // Order completed
    orderCompleted() {
        clearInterval(this.checkInterval);
        
        // Send success notification
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification("Order Completed! 🎉", {
                body: "Your data package has been delivered successfully!",
                icon: '/images/success.png',
                requireInteraction: true
            });
        }
        
        // Show success message
        this.showInPageNotification(
            "Success!",
            "Your data package has been delivered successfully!",
            "✅"
        );
        
        // Play success sound
        this.playSound('success');
    }

    // Order failed
    orderFailed() {
        clearInterval(this.checkInterval);
        
        // Send failure notification
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification("Order Issue", {
                body: "There was an issue with your order. Please contact support.",
                icon: '/images/error.png',
                requireInteraction: true
            });
        }
        
        this.showInPageNotification(
            "Order Issue",
            "Please contact support for assistance.",
            "❌"
        );
    }

    // Show in-page notification
    showInPageNotification(title, message, icon) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'page-notification';
        notification.innerHTML = `
            <div class="notification-icon">${icon}</div>
            <div class="notification-content">
                <h4>${title}</h4>
                <p>${message}</p>
            </div>
            <button onclick="this.parentElement.remove()">✕</button>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Auto-remove after 10 seconds
        setTimeout(() => {
            notification.remove();
        }, 10000);
    }

    // Setup local notifications (works even if page is closed)
    setupLocalNotifications() {
        // Store order details in localStorage
        const orderData = {
            orderId: this.orderId,
            startTime: Date.now(),
            notified: this.notificationTimes
        };
        
        localStorage.setItem('activeOrder', JSON.stringify(orderData));
        
        // Check for updates when page loads
        window.addEventListener('focus', () => {
            this.checkStoredOrder();
        });
    }

    // Check stored order
    checkStoredOrder() {
        const stored = localStorage.getItem('activeOrder');
        if (stored) {
            const orderData = JSON.parse(stored);
            const elapsed = (Date.now() - orderData.startTime) / 60000;
            
            // Update notification times
            this.notificationTimes = orderData.notified;
            
            // Check if we need to send any notifications
            if (elapsed >= 30 && !this.notificationTimes[30]) {
                this.sendNotification(30);
            }
            // ... etc
        }
    }

    // Play sound
    playSound(type) {
        const audio = new Audio(`/sounds/${type}.mp3`);
        audio.play().catch(e => console.log('Could not play sound:', e));
    }
}

// CSS for notifications
const notificationStyles = `
<style>
.page-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    max-width: 400px;
    z-index: 9999;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.notification-icon {
    font-size: 2rem;
}

.notification-content h4 {
    margin: 0 0 0.25rem 0;
    color: #1f2937;
}

.notification-content p {
    margin: 0;
    color: #6b7280;
    font-size: 0.875rem;
}

.page-notification button {
    position: absolute;
    top: 10px;
    right: 10px;
    background: none;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
    color: #6b7280;
}

.status-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.status-card.pending {
    border-left: 4px solid #f59e0b;
}

.status-card.processing {
    border-left: 4px solid #3b82f6;
}

.status-card.completed {
    border-left: 4px solid #10b981;
}

.status-card.failed {
    border-left: 4px solid #ef4444;
}

.status-icon {
    font-size: 2rem;
}

.status-text h3 {
    margin: 0 0 0.5rem 0;
    color: #1f2937;
}

.status-text p {
    margin: 0.25rem 0;
    color: #6b7280;
    font-size: 0.875rem;
}
</style>
`;

// Add styles to page
document.head.insertAdjacentHTML('beforeend', notificationStyles);
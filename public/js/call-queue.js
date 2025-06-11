/**
 * Call Queue Audio Handler
 * File ini yang hilang dan menyebabkan error 404
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('📞 Call Queue script loaded');

    // Listen for queue-called events
    if (window.Livewire) {
        window.Livewire.on('queue-called', (message) => {
            console.log('📞 Queue called:', message);
            handleQueueCall(message);
        });
    }

    // Also listen for document events as fallback
    document.addEventListener('livewire:initialized', () => {
        console.log('📞 Livewire initialized for call-queue');
        
        window.Livewire.on('queue-called', (message) => {
            console.log('📞 Queue called via Livewire:', message);
            handleQueueCall(message);
        });
    });
});

function handleQueueCall(message) {
    // Try to use QueueAudio if available
    if (window.QueueAudio && window.QueueAudio.isInitialized) {
        console.log('📞 Using QueueAudio system');
        window.QueueAudio.playQueueAudio(message);
    } else {
        console.log('📞 QueueAudio not available, using fallback');
        fallbackAudioAnnouncement(message);
    }
}

function fallbackAudioAnnouncement(message) {
    try {
        // Simple text-to-speech fallback
        if ('speechSynthesis' in window) {
            const utterance = new SpeechSynthesisUtterance(message);
            utterance.lang = 'id-ID';
            utterance.rate = 0.9;
            speechSynthesis.speak(utterance);
            console.log('📞 Fallback speech played');
        } else {
            console.warn('📞 Speech synthesis not supported');
            showVisualNotification(message);
        }
    } catch (error) {
        console.error('📞 Fallback audio error:', error);
        showVisualNotification(message);
    }
}

function showVisualNotification(message) {
    // Create visual notification
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #f59e0b;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 9999;
        max-width: 400px;
        font-weight: bold;
        animation: slideIn 0.3s ease-out;
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 20px;">🔊</span>
            <div>
                <div style="font-size: 14px; margin-bottom: 4px;">PANGGILAN ANTRIAN</div>
                <div style="font-size: 16px;">${message}</div>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer; margin-left: auto;">×</button>
        </div>
    `;

    document.body.appendChild(notification);

    // Auto remove after 8 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 8000);
}

// Add slideIn animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
`;
document.head.appendChild(style);

console.log('📞 Call Queue script ready');
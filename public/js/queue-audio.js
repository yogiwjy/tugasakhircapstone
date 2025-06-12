/**
 * Queue Audio System - All-in-One Simple Version
 * Handles audio announcements for queue calls
 */

window.QueueAudio = {
    isInitialized: false,
    
    // Initialize audio system
    init() {
        try {
            if ('speechSynthesis' in window) {
                this.isInitialized = true;
                console.log('✅ QueueAudio initialized successfully');
                return true;
            } else {
                console.error('❌ Speech Synthesis not supported');
                return false;
            }
        } catch (error) {
            console.error('❌ QueueAudio init error:', error);
            return false;
        }
    },
    
    // Main function to play audio
    speak(message) {
        console.log('🔊 QueueAudio speaking:', message);
        
        // Auto-init if not initialized
        if (!this.isInitialized) {
            this.init();
        }
        
        if (!this.isInitialized) {
            console.error('❌ Cannot speak - audio not initialized');
            return false;
        }
        
        try {
            // Stop any current speech
            speechSynthesis.cancel();
            
            // Create utterance
            const utterance = new SpeechSynthesisUtterance(message);
            utterance.rate = 0.9;
            utterance.volume = 1.0;
            utterance.lang = 'id-ID';
            
            // Try to find Indonesian voice
            const voices = speechSynthesis.getVoices();
            const indonesianVoice = voices.find(voice => 
                voice.lang.includes('id') || 
                voice.name.toLowerCase().includes('indonesia')
            );
            
            if (indonesianVoice) {
                utterance.voice = indonesianVoice;
                console.log('🎤 Using Indonesian voice:', indonesianVoice.name);
            }
            
            // Speak
            speechSynthesis.speak(utterance);
            console.log('✅ QueueAudio speech played successfully');
            return true;
            
        } catch (error) {
            console.error('❌ QueueAudio speech error:', error);
            return false;
        }
    },
    
    // Alias for backward compatibility
    playQueueAudio(message) {
        return this.speak(message);
    }
};

// Handle queue call events
function handleQueueCall(message) {
    console.log('📞 Handling queue call:', message);
    
    // Try QueueAudio first
    if (window.QueueAudio && window.QueueAudio.speak) {
        const success = window.QueueAudio.speak(message);
        if (success) {
            return;
        }
    }
    
    // Fallback to direct speech synthesis
    console.log('📞 Using direct speech synthesis fallback');
    try {
        if ('speechSynthesis' in window) {
            speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(message);
            utterance.lang = 'id-ID';
            utterance.rate = 0.9;
            speechSynthesis.speak(utterance);
            console.log('✅ Fallback speech played');
        } else {
            console.warn('❌ Speech synthesis not supported');
            showVisualNotification(message);
        }
    } catch (error) {
        console.error('❌ Fallback audio error:', error);
        showVisualNotification(message);
    }
}

// Visual notification fallback
function showVisualNotification(message) {
    console.log('📢 Showing visual notification');
    
    // Remove existing notifications
    const existing = document.querySelectorAll('.queue-notification');
    existing.forEach(el => el.remove());
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = 'queue-notification';
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

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎵 Queue Audio Script Loaded');
    
    // Initialize audio
    window.QueueAudio.init();
    
    // Add CSS for animations
    if (!document.getElementById('queue-audio-styles')) {
        const style = document.createElement('style');
        style.id = 'queue-audio-styles';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }
});

// Livewire event listeners
document.addEventListener('livewire:initialized', function() {
    console.log('🔧 Livewire initialized for queue audio');
    
    if (window.Livewire && window.Livewire.on) {
        window.Livewire.on('queue-called', function(message) {
            console.log('📞 Queue called via Livewire:', message);
            handleQueueCall(message);
        });
    }
});

// Also listen immediately if Livewire is already available
if (window.Livewire && window.Livewire.on) {
    window.Livewire.on('queue-called', function(message) {
        console.log('📞 Queue called (immediate):', message);
        handleQueueCall(message);
    });
}

// Global functions for testing and compatibility
window.playQueueAudio = function(message) {
    return window.QueueAudio.speak(message);
};

window.testAudio = function(message = 'Test audio nomor antrian A001 silakan ke loket 1') {
    console.log('🧪 Testing audio:', message);
    return window.QueueAudio.speak(message);
};

// Debug function
window.checkAudioStatus = function() {
    const status = {
        speechSynthesisSupported: 'speechSynthesis' in window,
        queueAudioAvailable: !!window.QueueAudio,
        queueAudioInitialized: window.QueueAudio ? window.QueueAudio.isInitialized : false,
        livewireAvailable: !!window.Livewire,
        voicesCount: speechSynthesis ? speechSynthesis.getVoices().length : 0
    };
    console.log('🔍 Audio Status:', status);
    return status;
};

console.log('✅ Queue Audio All-in-One Script Ready');
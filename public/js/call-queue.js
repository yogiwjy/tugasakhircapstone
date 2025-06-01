// Fungsi untuk mendapatkan voices dengan error handling yang lebih baik
function getVoices() {
    return new Promise((resolve) => {
        let attempts = 0;
        const maxAttempts = 100;
        
        const checkVoices = () => {
            const voices = window.speechSynthesis.getVoices();
            attempts++;
            
            if (voices.length > 0) {
                console.log('Voices loaded:', voices.length);
                resolve(voices);
            } else if (attempts >= maxAttempts) {
                console.warn('Timeout: No voices found');
                resolve([]);
            } else {
                setTimeout(checkVoices, 10);
            }
        };
        
        checkVoices();
    });
}

// Fungsi untuk memutar suara
async function playQueueSound(message) {
    try {
        console.log('Playing sound for message:', message);
        
        if (!window.speechSynthesis) {
            console.error('Speech synthesis not supported');
            return;
        }
        
        window.speechSynthesis.cancel();
        
        let voices = await getVoices();
        
        if (voices.length === 0) {
            console.warn('No voices available, using default');
            voices = window.speechSynthesis.getVoices();
        }
        
        const idVoices = voices.filter(voice => {
            return voice.lang.includes("id") || voice.lang.includes("ID");
        });
        
        console.log('Indonesian voices found:', idVoices.length);
        
        const speech = new SpeechSynthesisUtterance(message);
        
        if (idVoices.length > 0) {
            const selectedVoice = idVoices.find(voice => 
                voice.name.toLowerCase().includes('female') || 
                voice.name.toLowerCase().includes('woman')
            ) || idVoices[idVoices.length - 1];
            
            speech.voice = selectedVoice;
            console.log('Using voice:', selectedVoice.name);
        } else {
            console.warn('No Indonesian voices found, using default');
        }
        
        speech.rate = 0.8;
        speech.pitch = 1;
        speech.volume = 1;
        
        speech.onstart = () => console.log('Speech started');
        speech.onend = () => console.log('Speech ended');
        speech.onerror = (event) => console.error('Speech error:', event.error);
        
        window.speechSynthesis.speak(speech);
        
    } catch (error) {
        console.error('Error in playQueueSound:', error);
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, checking for queue call session');
    
    const queueFlash = document.querySelector('meta[name="queue-called"]');
    if (queueFlash) {
        try {
            const data = JSON.parse(queueFlash.content);
            console.log('Queue call data from session:', data);
            playQueueSound(data.message);
        } catch (error) {
            console.error('Error parsing queue data:', error);
        }
    }
});

// Global functions
window.callQueue = function(queueNumber, message) {
    const finalMessage = message || `Nomor antrian ${queueNumber} silakan menuju ruang periksa`;
    playQueueSound(finalMessage);
};

window.testQueueSound = function(message = "Test pesan antrian nomor A001 silakan menuju ruang periksa") {
    playQueueSound(message);
};

// Livewire support
if (typeof Livewire !== 'undefined') {
    document.addEventListener('livewire:initialized', () => {
        console.log('Livewire initialized, setting up queue-called listener');
        Livewire.on('queue-called', (data) => {
            console.log('queue-called event received:', data);
            const message = typeof data === 'string' ? data : data.message || data[0];
            playQueueSound(message);
        });
    });
}
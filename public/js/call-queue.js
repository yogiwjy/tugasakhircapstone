// Fungsi untuk mendapatkan voices dengan error handling yang lebih baik
function getVoices() {
    return new Promise((resolve, reject) => {
        let attempts = 0;
        const maxAttempts = 100; // Maximum 1 detik (100 x 10ms)
        
        const checkVoices = () => {
            const voices = window.speechSynthesis.getVoices();
            attempts++;
            
            if (voices.length > 0) {
                console.log('Voices loaded:', voices.length);
                resolve(voices);
            } else if (attempts >= maxAttempts) {
                console.warn('Timeout: No voices found');
                resolve([]); // Return empty array instead of rejecting
            } else {
                setTimeout(checkVoices, 10);
            }
        };
        
        checkVoices();
    });
}

// Fungsi untuk memutar suara dengan error handling yang lebih baik
async function playQueueSound(message) {
    try {
        console.log('Playing sound for message:', message);
        
        // Pastikan speechSynthesis tersedia
        if (!window.speechSynthesis) {
            console.error('Speech synthesis not supported');
            return;
        }
        
        // Stop any ongoing speech
        window.speechSynthesis.cancel();
        
        // Get available voices
        let voices = await getVoices();
        
        // Jika tidak ada voices, coba gunakan default
        if (voices.length === 0) {
            console.warn('No voices available, using default');
            voices = window.speechSynthesis.getVoices();
        }
        
        // Filter untuk bahasa Indonesia
        const idVoices = voices.filter(voice => {
            return voice.lang.includes("id") || voice.lang.includes("ID");
        });
        
        console.log('Indonesian voices found:', idVoices.length);
        
        // Buat utterance
        const speech = new SpeechSynthesisUtterance(message);
        
        // Set voice jika ada
        if (idVoices.length > 0) {
            // Pilih voice wanita jika ada, atau voice terakhir
            const selectedVoice = idVoices.find(voice => 
                voice.name.toLowerCase().includes('female') || 
                voice.name.toLowerCase().includes('woman')
            ) || idVoices[idVoices.length - 1];
            
            speech.voice = selectedVoice;
            console.log('Using voice:', selectedVoice.name);
        } else {
            console.warn('No Indonesian voices found, using default');
        }
        
        // Set properties
        speech.rate = 0.8;
        speech.pitch = 1;
        speech.volume = 1;
        
        // Event handlers untuk debugging
        speech.onstart = () => {
            console.log('Speech started');
        };
        
        speech.onend = () => {
            console.log('Speech ended');
        };
        
        speech.onerror = (event) => {
            console.error('Speech error:', event.error);
        };
        
        // Play the speech
        window.speechSynthesis.speak(speech);
        
    } catch (error) {
        console.error('Error in playQueueSound:', error);
    }
}

// Event listener dengan error handling
document.addEventListener('livewire:initialized', () => {
    console.log('Livewire initialized, setting up queue-called listener');
    
    Livewire.on('queue-called', (message) => {
        console.log('queue-called event received:', message);
        playQueueSound(message);
    });
});

// Backup: jika livewire:initialized tidak bekerja
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, checking for Livewire');
    
    // Tunggu Livewire ready
    const checkLivewire = () => {
        if (typeof Livewire !== 'undefined') {
            console.log('Livewire found, setting up backup listener');
            Livewire.on('queue-called', (message) => {
                console.log('queue-called event received (backup):', message);
                playQueueSound(message);
            });
        } else {
            setTimeout(checkLivewire, 100);
        }
    };
    
    checkLivewire();
});

// Test function untuk debugging (bisa dipanggil dari console)
window.testQueueSound = function(message = "Test pesan antrian") {
    playQueueSound(message);
};
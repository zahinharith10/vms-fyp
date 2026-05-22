<script setup>
import { onMounted, ref, onBeforeUnmount } from 'vue';
import { 
    nets, 
    matchDimensions, 
    detectSingleFace, 
    resizeResults, 
    draw 
} from 'face-api.js';

const props = defineProps({
    allowUpload: {
        type: Boolean,
        default: true
    }
});

const videoEl = ref(null);
const canvasEl = ref(null);
const isModelLoaded = ref(false);
const detection = ref(null);
const errorMessage = ref('');
let intervalId = null;

const mode = ref('camera'); // 'camera' or 'upload'
const uploadedImage = ref(null);
const uploadCanvasEl = ref(null);

const statusMessage = ref('Initializing...');

const loadModels = async () => {
    try {
        const MODEL_URL = '/models';
        console.log("Loading models from " + MODEL_URL);
        
        // Load the AI models from /public/models
        // 1. ssdMobilenetv1: The primary neural network for finding faces in images
        statusMessage.value = 'Loading SSD Mobilenet...';
        await nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
        
        // 2. faceLandmark68Net: Identifies the 68 points on a face (eyes, nose, mouth)
        statusMessage.value = 'Loading Face Landmark 68...';
        await nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        
        // 3. faceRecognitionNet: Converts the face into a 128-number mathematical descriptor
        statusMessage.value = 'Loading Face Recognition...';
        await nets.faceRecognitionNet.loadFromUri(MODEL_URL);
        
        console.log("Models loaded successfully");
        isModelLoaded.value = true;
        if (mode.value === 'camera') {
            startVideo();
        }
    } catch (err) {
        console.error("Model loading failed:", err);
        errorMessage.value = "Failed to load AI models: " + err.message;
    }
};

const startVideo = () => {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        errorMessage.value = "Camera access not supported in this browser.";
        return;
    }
    
    navigator.mediaDevices.getUserMedia({ video: {} })
        .then(stream => {
            if (videoEl.value) {
                videoEl.value.srcObject = stream;
            }
        })
        .catch(err => {
            console.error("Error loading video:", err);
            errorMessage.value = "Camera access denied or error: " + err.message;
        });
};

const emit = defineEmits(['face-detected']);

const onPlay = () => {
    if (!videoEl.value || !canvasEl.value) return;
    
    const displaySize = { width: videoEl.value.width, height: videoEl.value.height };
    matchDimensions(canvasEl.value, displaySize);

    intervalId = setInterval(async () => {
        if (!videoEl.value || videoEl.value.paused || videoEl.value.ended) return;

        try {
            // Core AI Task: Find a face, extract its landmarks, and generate the 128-bit descriptor
            const detections = await detectSingleFace(videoEl.value).withFaceLandmarks().withFaceDescriptor();
            
            const canvas = canvasEl.value;
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            if (detections) {
                detection.value = detections;
                emit('face-detected', detections);
                const resizedDetections = resizeResults(detections, displaySize);
                // Draw the AI results (boxes and points) on the canvas for the user to see
                draw.drawDetections(canvas, resizedDetections);
                draw.drawFaceLandmarks(canvas, resizedDetections);
            } else {
                detection.value = null;
                emit('face-detected', null);
            }
        } catch (err) {
           console.error("Detection error:", err);
        }
    }, 100);
};

const stopVideo = () => {
    if (intervalId) clearInterval(intervalId);
    if (videoEl.value && videoEl.value.srcObject) {
        videoEl.value.srcObject.getTracks().forEach(track => track.stop());
        videoEl.value.srcObject = null;
    }
};

const switchMode = (newMode) => {
    mode.value = newMode;
    errorMessage.value = '';
    detection.value = null;
    emit('face-detected', null);

    if (newMode === 'camera') {
        uploadedImage.value = null;
        if (isModelLoaded.value) startVideo();
    } else {
        stopVideo();
    }
};

const handleFileUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
        errorMessage.value = "Please upload an image file.";
        return;
    }

    const reader = new FileReader();
    reader.onload = async (e) => {
        const img = new Image();
        img.src = e.target.result;
        img.onload = async () => {
            uploadedImage.value = img.src; // Show preview
            
            // Wait for Vue to update DOM to get canvas ref if needed, 
            // but we can process detection on the image object directly.
            
            try {
                if (!uploadCanvasEl.value) return;
                
                // Set canvas dimensions to match image
                const displaySize = { width: img.width, height: img.height };
                // Limit display size for UI if image is huge? 
                // For now, let's keep original size for accurate descriptor or resize for display.
                // Better: Draw image to canvas scaled to fit container, then detect.
                // Simple version: Detect on image object.
                
                const canvas = uploadCanvasEl.value;
                canvas.width = 400; // Fixed preview width
                const scale = 400 / img.width;
                canvas.height = img.height * scale;
                
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                
                // Detect on the DRAWN canvas to match what we see? 
                // Or detect on original image for simpler code. 
                // Let's detect on the ORIGINAL image element for best quality descriptor.
                // But we need to draw results on the canvas which is scaled.
                
                statusMessage.value = "Processing image...";
                const detections = await detectSingleFace(img).withFaceLandmarks().withFaceDescriptor();
                
                if (detections) {
                    detection.value = detections;
                    emit('face-detected', detections);
                    
                    // Draw results on the preview canvas
                    // We need to resize results to match the SCALED canvas
                    const resizedDetections = resizeResults(detections, { width: canvas.width, height: canvas.height });
                    draw.drawDetections(canvas, resizedDetections);
                    draw.drawFaceLandmarks(canvas, resizedDetections);
                    statusMessage.value = "";
                } else {
                    errorMessage.value = "No face detected in this photo. Please clear and try another.";
                    detection.value = null;
                    emit('face-detected', null);
                    statusMessage.value = "";
                }
            } catch (err) {
                console.error("Upload detection error:", err);
                errorMessage.value = "Error processing image.";
            }
        };
    };
    reader.readAsDataURL(file);
};

// Snapshot for uploading the face image to server
const getSnapshot = () => {
    return new Promise(resolve => {
        if (mode.value === 'camera') {
            if (!videoEl.value) return resolve(null);
            const canvas = document.createElement('canvas');
            canvas.width = videoEl.value.videoWidth;
            canvas.height = videoEl.value.videoHeight;
            // Draw current video frame to canvas to "pause" it and export as file
            canvas.getContext('2d').drawImage(videoEl.value, 0, 0);
            canvas.toBlob(blob => {
                if(blob) resolve(new File([blob], "face_capture.jpg", { type: "image/jpeg" }));
                else resolve(null);
            }, 'image/jpeg', 0.95); // Export as high-quality JPEG for storage
        } else {
            // In upload mode, we return the uploaded file itself if we have base64
            // But getSnapshot expects a File object.
            // We can convert the uploadedImage (base64) back to a File/Blob if needed
            // OR we can just rely on the parent form having the original file?
            // Parent component calls getSnapshot().
            // Let's return a Blob from the canvas we drew on? No that's resized.
            // Let's convert uploadedImage.value (dataURL) to blob.
             if (!uploadedImage.value) return resolve(null);
             
             fetch(uploadedImage.value)
                .then(res => res.blob())
                .then(blob => {
                    resolve(new File([blob], "uploaded_face.jpg", { type: "image/jpeg" }));
                });
        }
    });
};

defineExpose({ getSnapshot });

onMounted(() => {
    loadModels();
});

onBeforeUnmount(() => {
    stopVideo();
});
</script>

<template>
    <div class="flex flex-col items-center w-full">
        <!-- Tabs -->
        <div v-if="allowUpload" class="flex space-x-4 mb-4 bg-gray-100 p-1 rounded-lg">
            <button 
                @click="switchMode('camera')"
                class="px-4 py-2 rounded-md text-sm font-bold transition-colors"
                :class="mode === 'camera' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
            >
                📷 Use Camera
            </button>
            <button 
                @click="switchMode('upload')"
                class="px-4 py-2 rounded-md text-sm font-bold transition-colors"
                :class="mode === 'upload' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
            >
                📤 Upload Photo
            </button>
        </div>

        <div v-if="errorMessage" class="mb-4 p-4 bg-red-100 text-red-700 rounded border border-red-400 w-full text-center text-sm">
            {{ errorMessage }}
        </div>
        <div v-else-if="!isModelLoaded || (mode==='upload' && statusMessage)" class="text-gray-500 mb-4 text-sm animate-pulse">{{ statusMessage }}</div>

        <!-- Camera Mode -->
        <div v-if="mode === 'camera'" class="relative">
            <video 
                ref="videoEl" 
                width="640" 
                height="480" 
                autoplay 
                muted 
                @play="onPlay"
                class="rounded-lg shadow-lg max-w-full h-auto"
            ></video>
            <canvas 
                ref="canvasEl" 
                class="absolute top-0 left-0 max-w-full h-auto"
            ></canvas>
        </div>

        <!-- Upload Mode -->
        <div v-else class="flex flex-col items-center w-full">
            <div v-if="!uploadedImage" class="w-full max-w-md h-64 border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center bg-gray-50 hover:bg-gray-100 transition-colors cursor-pointer relative">
                <input type="file" @change="handleFileUpload" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" />
                <span class="text-4xl mb-2">📥</span>
                <span class="text-gray-500 font-bold">Click to upload photo</span>
                <span class="text-xs text-gray-400 mt-2">JPG, PNG supported</span>
            </div>

            <div v-else class="relative w-full flex justify-center">
                <canvas 
                    ref="uploadCanvasEl"
                    class="rounded-lg shadow-lg border border-gray-200"
                ></canvas>
                <button 
                    @click="uploadedImage = null; detection = null; emit('face-detected', null);"
                    class="absolute top-2 right-2 bg-red-600 text-white rounded-full p-2 hover:bg-red-700 shadow-lg"
                    title="Remove Photo"
                >
                    ✕
                </button>
            </div>
        </div>

        <!-- Status Indicator -->
        <div v-if="detection" class="mt-4 text-green-600 font-bold flex items-center bg-green-50 px-4 py-2 rounded-full border border-green-200 text-sm">
            <span class="mr-2">✅</span> Face Detected (Score: {{ detection.detection.score.toFixed(2) }})
        </div>
        <div v-else-if="isModelLoaded && mode === 'camera'" class="mt-4 text-orange-500 font-bold text-sm animate-pulse">
            Searching for face...
        </div>
    </div>
</template>

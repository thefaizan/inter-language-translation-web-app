<?php
// translate.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputLang = $_POST['inputLang'];
    $outputLang = $_POST['outputLang'];
    
    // Google Cloud API setup
    $apiKey = 'AIzaSyDI_9sIJODyU2rFoJ8ymGhDHZr3Dl_Hkdo'; // Replace with your Google API Key
    $translateUrl = 'https://translation.googleapis.com/language/translate/v2';
    
    // Get the uploaded audio file
    $audioFile = $_FILES['audio']['tmp_name'];
    
    // Convert the audio file to base64
    $audioData = base64_encode(file_get_contents($audioFile));

    file_put_contents('test_audio.wav', file_get_contents($audioFile));
    
    // Google Cloud Speech-to-Text API request
    $speechRequest = [
        'audio' => [
            'content' => $audioData,
        ],
        'config' => [
            'encoding' => 'WEBM_OPUS',
            'languageCode' => $inputLang,
        ],
    ];
    
    // Send request to Google Speech-to-Text API
    $ch = curl_init("https://speech.googleapis.com/v1/speech:recognize?key=$apiKey");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($speechRequest));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $speechResponse = curl_exec($ch);
    curl_close($ch);
    
    $speechData = json_decode($speechResponse, true);

    // Debugging - Log the full response for troubleshooting
    file_put_contents('speech_debug.log', print_r($speechData, true));

    if (isset($speechData['results']) && isset($speechData['results'][0]['alternatives'][0]['transcript'])) {
        $text = $speechData['results'][0]['alternatives'][0]['transcript'];
    } else {
        echo "Error: Speech-to-Text failed or no transcript found.";
        exit;
    }

    // Translation request to Google Translate API
    $translateRequest = [
        'q' => $text,
        'source' => $inputLang,
        'target' => $outputLang,
        'format' => 'text'
    ];

    $ch = curl_init("$translateUrl?key=$apiKey");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($translateRequest));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $translateResponse = curl_exec($ch);
    curl_close($ch);
    
    $translateData = json_decode($translateResponse, true);
    
    if (isset($translateData['data']) && isset($translateData['data']['translations'][0]['translatedText'])) {
        $translatedText = $translateData['data']['translations'][0]['translatedText'];
    } else {
        echo "Error: Translation failed or no translated text found.";
        exit;
    }

    // Text-to-Speech request to Google Text-to-Speech API
    $ttsRequest = [
        'input' => ['text' => $translatedText],
        'voice' => [
            'languageCode' => $outputLang,
            'ssmlGender' => 'NEUTRAL'
        ],
        'audioConfig' => ['audioEncoding' => 'LINEAR16']
    ];

    $ch = curl_init("https://texttospeech.googleapis.com/v1/text:synthesize?key=$apiKey");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ttsRequest));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $ttsResponse = curl_exec($ch);
    curl_close($ch);

    $ttsData = json_decode($ttsResponse, true);

    // Debugging - Log the full response for troubleshooting
    file_put_contents('tts_debug.log', print_r($ttsData, true));

    if (isset($ttsData['audioContent'])) {
        $audioContent = $ttsData['audioContent'];
        echo $audioContent; // Return base64 audio
    } else {
        echo "Error: Text-to-Speech failed or no audio content found.";
    }
}
?>

/**
 * TirahAI Arduino Interface
 * 
 * This sketch enables TirahAI to control Arduino pins via serial communication.
 * 
 * Supported Commands:
 * - SET,pin,value     : Set digital pin (0 or 1)
 * - PWM,pin,value     : Set PWM value (0-255)
 * - READD,pin         : Read digital pin
 * - READA,pin         : Read analog pin
 * 
 * Example:
 * Send: "SET,13,1" to turn on LED on pin 13
 * Send: "PWM,9,128" to set pin 9 to 50% brightness
 * Send: "READA,0" to read analog pin A0
 */

const int LED_PIN = 13;  // Built-in LED

void setup() {
  // Initialize serial communication
  Serial.begin(9600);
  
  // Configure built-in LED as output
  pinMode(LED_PIN, OUTPUT);
  
  // Send ready signal
  Serial.println("READY");
  
  // Blink LED to indicate ready
  for(int i = 0; i < 3; i++) {
    digitalWrite(LED_PIN, HIGH);
    delay(200);
    digitalWrite(LED_PIN, LOW);
    delay(200);
  }
}

void loop() {
  // Check if data is available
  if (Serial.available() > 0) {
    // Read command until newline
    String command = Serial.readStringUntil('\n');
    command.trim();
    
    // Process command
    processCommand(command);
  }
}

/**
 * Process incoming command
 */
void processCommand(String command) {
  // Parse command type
  int firstComma = command.indexOf(',');
  
  if (firstComma == -1) {
    Serial.println("ERROR: Invalid command format");
    return;
  }
  
  String cmdType = command.substring(0, firstComma);
  String params = command.substring(firstComma + 1);
  
  // Execute based on command type
  if (cmdType == "SET") {
    handleDigitalWrite(params);
  }
  else if (cmdType == "PWM") {
    handlePWMWrite(params);
  }
  else if (cmdType == "READD") {
    handleDigitalRead(params);
  }
  else if (cmdType == "READA") {
    handleAnalogRead(params);
  }
  else {
    Serial.println("ERROR: Unknown command");
  }
}

/**
 * Handle digital write command
 * Format: SET,pin,value
 */
void handleDigitalWrite(String params) {
  int comma = params.indexOf(',');
  
  if (comma == -1) {
    Serial.println("ERROR: Invalid SET format");
    return;
  }
  
  int pin = params.substring(0, comma).toInt();
  int value = params.substring(comma + 1).toInt();
  
  // Validate
  if (pin < 0 || pin > 13) {
    Serial.println("ERROR: Invalid pin number");
    return;
  }
  
  if (value != 0 && value != 1) {
    Serial.println("ERROR: Value must be 0 or 1");
    return;
  }
  
  // Set pin mode and write
  pinMode(pin, OUTPUT);
  digitalWrite(pin, value);
  
  Serial.print("OK: Pin ");
  Serial.print(pin);
  Serial.print(" set to ");
  Serial.println(value);
}

/**
 * Handle PWM write command
 * Format: PWM,pin,value
 */
void handlePWMWrite(String params) {
  int comma = params.indexOf(',');
  
  if (comma == -1) {
    Serial.println("ERROR: Invalid PWM format");
    return;
  }
  
  int pin = params.substring(0, comma).toInt();
  int value = params.substring(comma + 1).toInt();
  
  // Validate PWM pins (3, 5, 6, 9, 10, 11 on most boards)
  if (pin != 3 && pin != 5 && pin != 6 && pin != 9 && pin != 10 && pin != 11) {
    Serial.println("ERROR: Not a PWM pin");
    return;
  }
  
  if (value < 0 || value > 255) {
    Serial.println("ERROR: PWM value must be 0-255");
    return;
  }
  
  // Set pin mode and write
  pinMode(pin, OUTPUT);
  analogWrite(pin, value);
  
  Serial.print("OK: Pin ");
  Serial.print(pin);
  Serial.print(" PWM set to ");
  Serial.println(value);
}

/**
 * Handle digital read command
 * Format: READD,pin
 */
void handleDigitalRead(String params) {
  int pin = params.toInt();
  
  // Validate
  if (pin < 0 || pin > 13) {
    Serial.println("ERROR: Invalid pin number");
    return;
  }
  
  // Set pin mode and read
  pinMode(pin, INPUT);
  int value = digitalRead(pin);
  
  // Return value only (for parsing)
  Serial.println(value);
}

/**
 * Handle analog read command
 * Format: READA,pin
 */
void handleAnalogRead(String params) {
  int pin = params.toInt();
  
  // Validate (A0-A5 typically)
  if (pin < 0 || pin > 5) {
    Serial.println("ERROR: Invalid analog pin");
    return;
  }
  
  // Read analog value
  int value = analogRead(pin);
  
  // Return value only (for parsing)
  Serial.println(value);
}

/**
 * Helper function to get value from comma-separated string
 */
String getValue(String data, char separator, int index) {
  int found = 0;
  int strIndex[] = {0, -1};
  int maxIndex = data.length() - 1;

  for (int i = 0; i <= maxIndex && found <= index; i++) {
    if (data.charAt(i) == separator || i == maxIndex) {
      found++;
      strIndex[0] = strIndex[1] + 1;
      strIndex[1] = (i == maxIndex) ? i+1 : i;
    }
  }

  return found > index ? data.substring(strIndex[0], strIndex[1]) : "";
}


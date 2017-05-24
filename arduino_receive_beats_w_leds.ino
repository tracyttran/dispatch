/*
 * Plug arduino into USB, COM4. Upload this to arduino. 
 * Run dxarts471_seattlepdbeats.php from terminal. 
 * Hook up LEDs according to "Seattle Police Beats Instructions.docx"
 */
#include <Adafruit_NeoPixel.h>

#define STRIP_PIN 6 // the input pin
#define HOURS 24
#define NUM_REGIONS 51
#define NUM_LEDS 51

Adafruit_NeoPixel strip = Adafruit_NeoPixel(NUM_LEDS, STRIP_PIN, NEO_GRB + NEO_KHZ800);

/*
 * Messages are individually sent via serial. Each message is 24 hours of data for a specific region. 
 * messagesRead is a counter that denotes which index to put the next 24 hours of data in. 
 */
static int messagesRead;

/*
 * Outer array is 24 hours. For each hour, 51 regions that are either on/off. 
 */
boolean hours_arr[HOURS][NUM_REGIONS];

/* which precinct each region belongs to
 *  north = 0; 
 *  south = 1;
 *  southwest = 2; 
 *  east = 3;
 *  west = 4;
 *  special ballard = 5;
 */
int precincts[] = {
  2,
  2, 
  2,
  1, 
  1,
  1,
  1,
  1,
  1,
  1,
  2,
  2,
  2,
  4,
  4,
  3,
  3,
  4,
  1,
  3,
  4,
  4,
  4,
  4,
  4,
  3,
  3,
  1,
  4,
  3,
  3,
  3,
  0,
  0,
  0,
  0,
  0,
  0,
  3,
  4,
  4,
  0,
  5,
  4,
  0,
  0,
  0,
  0,
  0,
  0,
  0
};

//the GRB values for each precinct
int north[] = {0, 242, 255};
int south[] = {255, 255, 0};
int southwest[] = {85, 255, 0};
int east[] = {0, 0, 255};
int west[] = {255, 0, 0};
int ballard[] = {0, 255, 0};


void setup() {
  Serial.begin(9600);
  strip.begin(); 
  messagesRead = 0;
}

void loop() {
  if (true) { // don't start displaying until we have all info
    /*
     * for each hour, set each led to on/off based on if there was a 911 dispatch during that hour
     * show leds
     * delay 
     * go to next hour
     */
    for (int hrs = 0; hrs < HOURS; hrs++) {
      for (int led = 0; led < NUM_LEDS; led++) {
          boolean on = hours_arr[hrs][led];
          if (on) {
            int precinct = precincts[led];
            int red;
            int blue;
            int green;
            if (precinct == 0) { //north
              red = north[1];
              green = north[0];
              blue = north[2];
            } else if (precinct == 1) { //south
              red = south[1];
              green = south[0];
              blue = south[2];
            } else if (precinct == 2) { //southwest
              red = southwest[1];
              green = southwest[0];
              blue = southwest[2];
            } else if (precinct == 3) { //east
              red = east[1];
              green = east[0];
              blue = east[2];
            } else if (precinct == 4) { //west 
              red = west[1];
              green = west[0];
              blue = west[2];
            } else { //ballard fab lab
              red = ballard[1];
              green = ballard[0];
              blue = ballard[2];
            }
            
            strip.setPixelColor(led, strip.Color(red, green, blue)); 
          } else {
            strip.setPixelColor(led, 0);
          }
      }
      strip.show();
      delay(1000);
    }
    //turn all off after 24 hr cycle
    for (int led = 0; led < NUM_LEDS; led++) {
      strip.setPixelColor(led, 0);
    }
    delay(1000);
  }
}

/*
 * Pull binary string messages from the serial port
 */
void serialEvent() {
  String message = "";
  boolean read = false;
  while (Serial.available()) {
    message = Serial.readStringUntil('\n');
    read = true;
  }
  int hour = 0; 
  for (int i = 0; i < message.length(); i++) {
      char c = message.charAt(i);
        boolean on = false;
        on = (c == '1');
        hours_arr[hour][messagesRead] = on;
        hour++;
        if (hour == HOURS) { // in case more than one message gets sent at a time
          messagesRead++;
          hour = 0;
        }
  }
}



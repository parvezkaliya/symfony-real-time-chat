[Symfony Real Time Chat System (PHP)
-----------------------------------

A simple real time chat application built using:
- Symfony Console (for WebSocket server)
- Ratchet (WebSocket library)
- ReactPHP Event Loop
- Redis (Pub/Sub for multi server sync)

This project is tested on Ubuntu 24.04 with PHP 8.1 and Redis 7.

-----------------------------------
Requirements
-----------------------------------
- Ubuntu Linux (tested on 24.04)
- PHP 8.1 or higher
- Composer
- Redis server

-----------------------------------
Installation
-----------------------------------
1. Clone this project
   git clone https://github.com/parvezkaliya/symfony-real-time-chat.git
   cd symfony-real-time-chat

2. Install dependencies
   composer install

3. If needed, install required PHP packages manually
   composer require cboden/ratchet
   composer require react/event-loop
   composer require react/socket
   composer require react/http
   composer require clue/redis-react

4. Install and start Redis
   sudo apt update
   sudo apt install redis-server
   sudo systemctl enable redis-server
   sudo systemctl start redis-server
   redis-cli ping
   (It should return PONG)

-----------------------------------
Running the Project
-----------------------------------
1. Start Redis server
   sudo systemctl start redis-server

2. Start one or more WebSocket servers (in separate terminals)
   php bin/console app:chat-server 8080
   php bin/console app:chat-server 8081
   php bin/console app:chat-server 8082

3. Start the HTTP server to serve chat.html
   php -S localhost:8000 -t public

4. Open the chat page in a browser
   http://localhost:8000/chat.html

   Choose server port (8080 or 8081 or 8082)
   Enter your name
   Start chatting

   Messages will sync across all connected servers through Redis.

-----------------------------------
Done
-----------------------------------
You now have a working multi server real time chat system on Ubuntu using
Symfony, Ratchet, ReactPHP and Redis.](http://localhost:8000/chat.html)

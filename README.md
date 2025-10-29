<h1>Symfony Real Time Chat System (PHP)</h1>

<p>
A simple real time chat application built using:
</p>

<ul>
  <li>Symfony Console (for WebSocket server)</li>
  <li>Ratchet (WebSocket library)</li>
  <li>ReactPHP Event Loop</li>
  <li>Redis (Pub/Sub for multi server sync)</li>
</ul>

<p>
This project is tested on <strong>Ubuntu 24.04</strong> with <strong>PHP 8.1</strong> and <strong>Redis 7</strong>.
</p>

<hr>

<h2>Requirements</h2>
<ul>
  <li>Ubuntu Linux (tested on 24.04)</li>
  <li>PHP 8.1 or higher</li>
  <li>Composer</li>
  <li>Redis server</li>
</ul>

<hr>

<h2>Installation</h2>

<ol>
  <li>
    Clone this project:
    <pre><code>git clone https://github.com/parvezkaliya/symfony-real-time-chat.git
cd symfony-real-time-chat
</code></pre>
  </li>

  <li>
    Install dependencies:
    <pre><code>composer install
</code></pre>
  </li>

  <li>
    If needed, install required PHP packages manually:
    <pre><code>composer require cboden/ratchet
composer require react/event-loop
composer require react/socket
composer require react/http
composer require clue/redis-react
</code></pre>
  </li>

  <li>
    Install and start Redis:
    <pre><code>sudo apt update
sudo apt install redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
redis-cli ping
# It should return PONG
</code></pre>
  </li>
</ol>

<hr>

<h2>Running the Project</h2>

<ol>
  <li>
    Start Redis server:
    <pre><code>sudo systemctl start redis-server
</code></pre>
  </li>

  <li>
    Start one or more WebSocket servers (in separate terminals):
    <pre><code>php bin/console app:chat-server 8080
php bin/console app:chat-server 8081
php bin/console app:chat-server 8082
</code></pre>
  </li>

  <li>
    Start the HTTP server to serve chat.html:
    <pre><code>php -S localhost:8000 -t public
</code></pre>
  </li>

  <li>
    Open the chat page in a browser:
    <pre><code>http://localhost:8000/chat.html
</code></pre>
    <ul>
      <li>Choose server port (8080 or 8081 or 8082)</li>
      <li>Enter your name</li>
      <li>Start chatting</li>
    </ul>
    <p>Messages will sync across all connected servers through Redis.</p>
  </li>
</ol>

<hr>

<h2>Done</h2>
<p>
You now have a working multi server real time chat system on Ubuntu using
<strong>Symfony</strong>, <strong>Ratchet</strong>, <strong>ReactPHP</strong>, and <strong>Redis</strong>.
</p>

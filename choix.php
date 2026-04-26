<?php
include("index.html");

?>
<!DOCTYPE html>
<!-- Coding By CodingNepal - youtube.com/codingnepal -->
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Infinite Card Slider JavaScript | CodingNepal</title>
    <link rel="stylesheet" href="styl.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fontawesome Link for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
    <script src="scrip.js" defer></script>
  </head>
  <body>
 
    <div id="carte">
        <h1> IMMOBILIER</h1>
    <div class="prod-vehicule">
      <i id="left" class="fa-solid fa-angle-left"></i>
      <ul class="prod-carousel">
            <a href="detprod.html">
            <li class="prod">
          <div class="prod-card">
            <div class="image-container">

                 <img src="img-prod/maison1.jpg">       
                <div class="price">$49.9</div>
            </div>
            <label class="favorite">
                <input checked="" type="checkbox">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000000">
                    <path d="M12 20a1 1 0 0 1-.437-.1C11.214 19.73 3 15.671 3 9a5 5 0 0 1 8.535-3.536l.465.465.465-.465A5 5 0 0 1 21 9c0 6.646-8.212 10.728-8.562 10.9A1 1 0 0 1 12 20z"></path>
                </svg>
            </label>
        
            <div class="content">
                <div class="brand">ADIDAS</div>
                <div class="product-name">Classic oversized hoodie</div>
                <p id="countdown-1">Fin de la vente dans</p>
                <span id="time-1">01j 02h 53m 46s</span>
            </div>
        
            <div class="button-container">
                <button class="buy-button button">Buy Now</button>
                <button class="cart-button button">
                    <svg viewBox="0 0 27.97 25.074" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0,1.175A1.173,1.173,0,0,1,1.175,0H3.4A2.743,2.743,0,0,1,5.882,1.567H26.01A1.958,1.958,0,0,1,27.9,4.035l-2.008,7.459a3.532,3.532,0,0,1-3.4,2.61H8.36l.264,1.4a1.18,1.18,0,0,0,1.156.955H23.9a1.175,1.175,0,0,1,0,2.351H9.78a3.522,3.522,0,0,1-3.462-2.865L3.791,2.669A.39.39,0,0,0,3.4,2.351H1.175A1.173,1.173,0,0,1,0,1.175ZM6.269,22.724a2.351,2.351,0,1,1,2.351,2.351A2.351,2.351,0,0,1,6.269,22.724Zm16.455-2.351a2.351,2.351,0,1,1-2.351,2.351A2.351,2.351,0,0,1,22.724,20.373Z" id="cart-shopping-solid"></path>
                    </svg>
        
                </button>
            </div>
        </div>
        </li>
        </a> 
        <a href="detprod.html">
        <li class="prod">
          <div class="prod-card">
            <div class="image-container">

            <img src="img-prod/maison2.jpg">      
                <div class="price">$49.9</div>
            </div>
            <label class="favorite">
                <input checked="" type="checkbox">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000000">
                    <path d="M12 20a1 1 0 0 1-.437-.1C11.214 19.73 3 15.671 3 9a5 5 0 0 1 8.535-3.536l.465.465.465-.465A5 5 0 0 1 21 9c0 6.646-8.212 10.728-8.562 10.9A1 1 0 0 1 12 20z"></path>
                </svg>
            </label>
        
            <div class="content">
                <div class="brand">ADIDAS</div>
                <div class="product-name">Classic oversized hoodie</div>
                <p id="countdown-2">Fin de la vente dans</p>
                <span id="time-2">01j 02h 53m 46s</span>
               
            </div>
        
            <div class="button-container">
                <button class="buy-button button">Buy Now</button>
                <button class="cart-button button">
                    <svg viewBox="0 0 27.97 25.074" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0,1.175A1.173,1.173,0,0,1,1.175,0H3.4A2.743,2.743,0,0,1,5.882,1.567H26.01A1.958,1.958,0,0,1,27.9,4.035l-2.008,7.459a3.532,3.532,0,0,1-3.4,2.61H8.36l.264,1.4a1.18,1.18,0,0,0,1.156.955H23.9a1.175,1.175,0,0,1,0,2.351H9.78a3.522,3.522,0,0,1-3.462-2.865L3.791,2.669A.39.39,0,0,0,3.4,2.351H1.175A1.173,1.173,0,0,1,0,1.175ZM6.269,22.724a2.351,2.351,0,1,1,2.351,2.351A2.351,2.351,0,0,1,6.269,22.724Zm16.455-2.351a2.351,2.351,0,1,1-2.351,2.351A2.351,2.351,0,0,1,22.724,20.373Z" id="cart-shopping-solid"></path>
                    </svg>
        
                </button>
            </div>
        </div>
        </li>
        </a>
        <a href="detprod.html">
        <li class="prod">
          <div class="prod-card">
            <div class="image-container">

            <img src="img-prod/maison3.jpg">        
                <div class="price">$49.9</div>
            </div>
            <label class="favorite">
                <input checked="" type="checkbox">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000000">
                    <path d="M12 20a1 1 0 0 1-.437-.1C11.214 19.73 3 15.671 3 9a5 5 0 0 1 8.535-3.536l.465.465.465-.465A5 5 0 0 1 21 9c0 6.646-8.212 10.728-8.562 10.9A1 1 0 0 1 12 20z"></path>
                </svg>
            </label>
        
            <div class="content">
                <div class="brand">ADIDAS</div>
                <div class="product-name">Classic oversized hoodie</div>
                <p id="countdown-3">Fin de la vente dans</p>
                <span id="time-3">01j 02h 53m 46s</span>
                
            </div>
        
            <div class="button-container">
                <button class="buy-button button">Buy Now</button>
                <button class="cart-button button">
                    <svg viewBox="0 0 27.97 25.074" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0,1.175A1.173,1.173,0,0,1,1.175,0H3.4A2.743,2.743,0,0,1,5.882,1.567H26.01A1.958,1.958,0,0,1,27.9,4.035l-2.008,7.459a3.532,3.532,0,0,1-3.4,2.61H8.36l.264,1.4a1.18,1.18,0,0,0,1.156.955H23.9a1.175,1.175,0,0,1,0,2.351H9.78a3.522,3.522,0,0,1-3.462-2.865L3.791,2.669A.39.39,0,0,0,3.4,2.351H1.175A1.173,1.173,0,0,1,0,1.175ZM6.269,22.724a2.351,2.351,0,1,1,2.351,2.351A2.351,2.351,0,0,1,6.269,22.724Zm16.455-2.351a2.351,2.351,0,1,1-2.351,2.351A2.351,2.351,0,0,1,22.724,20.373Z" id="cart-shopping-solid"></path>
                    </svg>
        
                </button>
            </div>
        </div>
        </li>
        </a>
        <a href="detprod.html">
        <li class="prod">
          <div class="prod-card">
            <div class="image-container">

            <img src="img-prod/maison4.jpg">        
                <div class="price">$49.9</div>
            </div>
            <label class="favorite">
                <input checked="" type="checkbox">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000000">
                    <path d="M12 20a1 1 0 0 1-.437-.1C11.214 19.73 3 15.671 3 9a5 5 0 0 1 8.535-3.536l.465.465.465-.465A5 5 0 0 1 21 9c0 6.646-8.212 10.728-8.562 10.9A1 1 0 0 1 12 20z"></path>
                </svg>
            </label>
        
            <div class="content">
                <div class="brand">ADIDAS</div>
                <div class="product-name">Classic oversized hoodie</div>
                <p id="countdown-4">Fin de la vente dans</p>
                <span id="time-4">01j 02h 53m 46s</span>
                
            </div>
        
            <div class="button-container">
                <button class="buy-button button">Buy Now</button>
                <button class="cart-button button">
                    <svg viewBox="0 0 27.97 25.074" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0,1.175A1.173,1.173,0,0,1,1.175,0H3.4A2.743,2.743,0,0,1,5.882,1.567H26.01A1.958,1.958,0,0,1,27.9,4.035l-2.008,7.459a3.532,3.532,0,0,1-3.4,2.61H8.36l.264,1.4a1.18,1.18,0,0,0,1.156.955H23.9a1.175,1.175,0,0,1,0,2.351H9.78a3.522,3.522,0,0,1-3.462-2.865L3.791,2.669A.39.39,0,0,0,3.4,2.351H1.175A1.173,1.173,0,0,1,0,1.175ZM6.269,22.724a2.351,2.351,0,1,1,2.351,2.351A2.351,2.351,0,0,1,6.269,22.724Zm16.455-2.351a2.351,2.351,0,1,1-2.351,2.351A2.351,2.351,0,0,1,22.724,20.373Z" id="cart-shopping-solid"></path>
                    </svg>
        
                </button>
            </div>
        </div>
        </li>
        </a>
        <a href="detprod.html">
        <li class="prod">
          <div class="prod-card">
            <div class="image-container">

            <img src="img-prod/maison5.jpg">       
                <div class="price">$49.9</div>
            </div>
            <label class="favorite">
                <input checked="" type="checkbox">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000000">
                    <path d="M12 20a1 1 0 0 1-.437-.1C11.214 19.73 3 15.671 3 9a5 5 0 0 1 8.535-3.536l.465.465.465-.465A5 5 0 0 1 21 9c0 6.646-8.212 10.728-8.562 10.9A1 1 0 0 1 12 20z"></path>
                </svg>
            </label>
        
            <div class="content">
                <div class="brand">ADIDAS</div>
                <div class="product-name">Classic oversized hoodie</div>
                <p id="countdown-5">Fin de la vente dans</p>
                <span id="time-5">01j 02h 53m 46s</span>
                
            </div>
        
            <div class="button-container">
                <button class="buy-button button">Buy Now</button>
                <button class="cart-button button">
                    <svg viewBox="0 0 27.97 25.074" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0,1.175A1.173,1.173,0,0,1,1.175,0H3.4A2.743,2.743,0,0,1,5.882,1.567H26.01A1.958,1.958,0,0,1,27.9,4.035l-2.008,7.459a3.532,3.532,0,0,1-3.4,2.61H8.36l.264,1.4a1.18,1.18,0,0,0,1.156.955H23.9a1.175,1.175,0,0,1,0,2.351H9.78a3.522,3.522,0,0,1-3.462-2.865L3.791,2.669A.39.39,0,0,0,3.4,2.351H1.175A1.173,1.173,0,0,1,0,1.175ZM6.269,22.724a2.351,2.351,0,1,1,2.351,2.351A2.351,2.351,0,0,1,6.269,22.724Zm16.455-2.351a2.351,2.351,0,1,1-2.351,2.351A2.351,2.351,0,0,1,22.724,20.373Z" id="cart-shopping-solid"></path>
                    </svg>
        
                </button>
            </div>
        </div>
        </li>
        </a>
       <a href="detprod.html">
       <li class="prod">
          <div class="prod-card">
            <div class="image-container">

                <img src="img-prod/maison6.jpg">       
                <div class="price">$49.9</div>
            </div>
            <label class="favorite">
                <input checked="" type="checkbox">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000000">
                    <path d="M12 20a1 1 0 0 1-.437-.1C11.214 19.73 3 15.671 3 9a5 5 0 0 1 8.535-3.536l.465.465.465-.465A5 5 0 0 1 21 9c0 6.646-8.212 10.728-8.562 10.9A1 1 0 0 1 12 20z"></path>
                </svg>
            </label>
        
            <div class="content">
                <div class="brand">ADIDAS</div>
                <div class="product-name">Classic oversized hoodie</div>
                <p id="countdown-6">Fin de la vente dans</p>
                <span id="time-6">01j 02h 53m 46s</span>
                
            </div>
        
            <div class="button-container">
                <button class="buy-button button">Buy Now</button>
                <button class="cart-button button">
                    <svg viewBox="0 0 27.97 25.074" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0,1.175A1.173,1.173,0,0,1,1.175,0H3.4A2.743,2.743,0,0,1,5.882,1.567H26.01A1.958,1.958,0,0,1,27.9,4.035l-2.008,7.459a3.532,3.532,0,0,1-3.4,2.61H8.36l.264,1.4a1.18,1.18,0,0,0,1.156.955H23.9a1.175,1.175,0,0,1,0,2.351H9.78a3.522,3.522,0,0,1-3.462-2.865L3.791,2.669A.39.39,0,0,0,3.4,2.351H1.175A1.173,1.173,0,0,1,0,1.175ZM6.269,22.724a2.351,2.351,0,1,1,2.351,2.351A2.351,2.351,0,0,1,6.269,22.724Zm16.455-2.351a2.351,2.351,0,1,1-2.351,2.351A2.351,2.351,0,0,1,22.724,20.373Z" id="cart-shopping-solid"></path>
                    </svg>
        
                </button>
            </div>
        </div>
        </li>
       </a>
       
      </ul>
      <i id="right" class="fa-solid fa-angle-right"></i>
    </div>
  </div>

<div>
<script>
    // Set the end dates for each auction
const endDate1 = new Date('2024-11-01T02:53:46.000Z');
const endDate2 = new Date('2024-10-01T02:53:46.000Z');
const endDate3 = new Date('2024-10-02T04:53:46.000Z');
const endDate4 = new Date('2024-11-01T02:53:46.000Z');
const endDate5 = new Date('2024-10-01T02:53:46.000Z');
const endDate6 = new Date('2024-10-02T04:53:46.000Z');

// Update the countdown timers every second
setInterval(() => {
  const now = new Date();
  const timeDiff1 = endDate1 - now;
  const timeDiff2 = endDate2 - now;
  const timeDiff3 = endDate3 - now;
  const timeDiff4 = endDate4 - now;
  const timeDiff5 = endDate5 - now;
  const timeDiff6 = endDate6 - now;



  const days1 = Math.floor(timeDiff1 / (1000 * 60 * 60 * 24));
  const hours1 = Math.floor((timeDiff1 % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  const minutes1 = Math.floor((timeDiff1 % (1000 * 60 * 60)) / (1000 * 60));
  const seconds1 = Math.floor((timeDiff1 % (1000 * 60)) / 1000);
  
  const days2 = Math.floor(timeDiff2 / (1000 * 60 * 60 * 24));
  const hours2 = Math.floor((timeDiff2 % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  const minutes2 = Math.floor((timeDiff2 % (1000 * 60 * 60)) / (1000 * 60));
  const seconds2 = Math.floor((timeDiff2 % (1000 * 60)) / 1000);

  const days3 = Math.floor(timeDiff3 / (1000 * 60 * 60 * 24));
  const hours3 = Math.floor((timeDiff3 % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  const minutes3 = Math.floor((timeDiff3 % (1000 * 60 * 60)) / (1000 * 60));
  const seconds3 = Math.floor((timeDiff3 % (1000 * 60)) / 1000);

  const days4 = Math.floor(timeDiff4 / (1000 * 60 * 60 * 24));
  const hours4 = Math.floor((timeDiff4 % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  const minutes4 = Math.floor((timeDiff4 % (1000 * 60 * 60)) / (1000 * 60));
  const seconds4 = Math.floor((timeDiff4 % (1000 * 60)) / 1000);
  
  const days5 = Math.floor(timeDiff5 / (1000 * 60 * 60 * 24));
  const hours5 = Math.floor((timeDiff5 % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  const minutes5 = Math.floor((timeDiff5 % (1000 * 60 * 60)) / (1000 * 60));
  const seconds5 = Math.floor((timeDiff5 % (1000 * 60)) / 1000);

  const days6 = Math.floor(timeDiff6 / (1000 * 60 * 60 * 24));
  const hours6 = Math.floor((timeDiff6 % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  const minutes6 = Math.floor((timeDiff6 % (1000 * 60 * 60)) / (1000 * 60));
  const seconds6 = Math.floor((timeDiff6 % (1000 * 60)) / 1000);


  document.getElementById('time-1').innerHTML = `${days1}j ${hours1}h ${minutes1}m ${seconds1}s`;
  document.getElementById('time-2').innerHTML = `${days2}j ${hours2}h ${minutes2}m ${seconds2}s`;
  document.getElementById('time-3').innerHTML = `${days3}j ${hours3}h ${minutes3}m ${seconds3}s`;
  document.getElementById('time-4').innerHTML = `${days4}j ${hours4}h ${minutes4}m ${seconds4}s`;
  document.getElementById('time-5').innerHTML = `${days5}j ${hours5}h ${minutes5}m ${seconds5}s`;
  document.getElementById('time-6').innerHTML = `${days6}j ${hours6}h ${minutes6}m ${seconds6}s`;
}, 1000);
  </script>
    <?php
include("about.php");
?>
</div>

  </body>
</html>
        

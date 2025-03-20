
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <h1>Slot Machine</h1>
    <div class="slot-machine">
        <div class="slot"><img src="apfel.png" alt="Symbol"></div>
        <div class="slot"><img src="zitrone.png" alt="Symbol"></div>
        <div class="slot"><img src="sieben.png" alt="Symbol"></div>
    </div>
    <button class="spin-btn">Spin</button>
    <div class="message"></div>

    <script>
        $(document).ready(function() {
            let symbols = ["apfel.png", "zitrone.png", "sieben.png"];
            
            $(".spin-btn").click(function() {
                $(".message").text("Spinning...");
                
                $(".slot img").each(function(index) {
                    let randomSymbol = symbols[Math.floor(Math.random() * symbols.length)];
                    $(this).fadeOut(200, function() {
                        $(this).attr("src", randomSymbol).fadeIn(200);
                    });
                });
                
                setTimeout(function() {
                    let imgSources = $(".slot img").map(function() {
                        return $(this).attr("src");
                    }).get();
                    
                    if (imgSources[0] === imgSources[1] && imgSources[1] === imgSources[2]) {
                        $(".message").text("Glückwunsch! Du hast gewonnen!");
                    } else {
                        $(".message").text("Leider verloren. Versuche es erneut!");
                    }
                }, 1000);
            });
        });
    </script>
</body>
</html>

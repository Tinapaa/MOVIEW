<?php
// ================== DATABASE CONNECTION ==================
$conn = new mysqli("localhost", "root", "", "movie_booking");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ================== GET MOVIE ID ==================
$movie_id = $_GET['movie_id'] ?? 0;

// ================== SAVE BOOKING (AJAX) ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $movie_id = $data['movie_id'];
    $seats = $data['seats'];

    foreach ($seats as $seat) {
        $stmt = $conn->prepare(
            "INSERT INTO bookings (movie_id, seat) VALUES (?, ?)"
        );
        $stmt->bind_param("is", $movie_id, $seat);
        $stmt->execute();
    }

    echo json_encode(["status" => "success"]);
    exit;
}

// ================== LOAD OCCUPIED SEATS ==================
$occupiedSeats = [];

$stmt = $conn->prepare("SELECT seat FROM bookings WHERE movie_id = ?");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $occupiedSeats[] = $row['seat'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seat Booking</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>

<!-- NAV BAR -->
<div class="top">
    <div class="nav-bar">
        <a href="index.html" class="nav-logo"> 🎬 MovieBooking</a>
        <ul class="nav-links">
            <li><a href="index.html">Home</a></li>
            <li><a href="movies.html">Movies</a></li>
            <li><a href="#">Bookings</a></li>
            <div id="nav-btn"></div>
        </ul>
    </div>
</div>

<div class="container seat-container">

    <h2>Select Your Seats</h2>

    <div class="screen">SCREEN THIS WAY</div>

    <!-- VIP -->
    <div class="seat-section">
        <p class="seat-label">VIP • ₱640</p>
        <div class="seat-row">
            <span class="row-label">K</span>
            <div class="seat">K1</div>
            <div class="seat">K2</div>
            <div class="seat">K3</div>
            <div class="seat">K4</div>
        </div>
    </div>

    <!-- EXECUTIVE -->
    <div class="seat-section">
        <p class="seat-label">EXECUTIVE • ₱320</p>
        <div class="seat-row">
            <span class="row-label">E</span>
            <div class="seat">E1</div>
            <div class="seat">E2</div>
            <div class="seat">E3</div>
        </div>
        <div class="seat-row">
            <span class="row-label">D</span>
            <div class="seat">D1</div>
            <div class="seat">D2</div>
            <div class="seat">D3</div>
        </div>
    </div>

    <!-- NORMAL -->
    <div class="seat-section">
        <p class="seat-label">NORMAL • ₱300</p>
        <div class="seat-row">
            <span class="row-label">A</span>
            <div class="seat">A1</div>
            <div class="seat">A2</div>
        </div>
    </div>

    <!-- LEGEND -->
    <div class="seat-legend">
        <div><span class="seat available"></span> Available</div>
        <div><span class="seat occupied"></span> Occupied</div>
        <div><span class="seat selected"></span> Selected</div>
    </div>

    <!-- SUMMARY -->
    <div class="booking-summary">
        <p>Selected Seats: <span id="selectedSeats">None</span></p>
        <p>Total: ₱<span id="total">0</span></p>
        <button class="btn-reco" onclick="confirmBooking()">Pay & Book</button>
    </div>

</div>

<script>
const seats = document.querySelectorAll('.seat');
const selectedSeatsEl = document.getElementById('selectedSeats');
const totalEl = document.getElementById('total');

const prices = {
    K: 640,
    E: 320,
    D: 320,
    A: 300
};

let selected = [];
const movieId = <?php echo (int)$movie_id; ?>;
const occupiedSeats = <?php echo json_encode($occupiedSeats); ?>;

// MARK OCCUPIED SEATS
seats.forEach(seat => {
    if (occupiedSeats.includes(seat.textContent)) {
        seat.classList.add('occupied');
    }
});

// CLICK SEATS
seats.forEach(seat => {
    if (seat.classList.contains('occupied')) return;

    seat.addEventListener('click', () => {
        seat.classList.toggle('selected');
        const name = seat.textContent;

        if (selected.includes(name)) {
            selected = selected.filter(s => s !== name);
        } else {
            selected.push(name);
        }
        updateTotal();
    });
});

function updateTotal() {
    let total = 0;
    selected.forEach(seat => {
        total += prices[seat[0]];
    });
    selectedSeatsEl.textContent = selected.length ? selected.join(', ') : 'None';
    totalEl.textContent = total;
}

function confirmBooking() {
    if (!selected.length) {
        alert("Please select seats");
        return;
    }

    fetch("seat-booking.php?movie_id=" + movieId, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            movie_id: movieId,
            seats: selected
        })
    })
    .then(res => res.json())
    .then(() => {
        alert("Booking successful!");
        location.reload();
    });
}
</script>

</body>
</html>

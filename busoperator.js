// DOM Elements
const navButtons = document.querySelectorAll("nav button")
const sections = document.querySelectorAll("section")
const seatEditModal = document.getElementById("seatEditModal")
const scheduleEditModal = document.getElementById("scheduleEditModal")
const bookingEditModal = document.getElementById("bookingEditModal")
const closeButtons = document.querySelectorAll(".close")

// Navigation
navButtons.forEach((button) => {
  button.addEventListener("click", () => {
    // Update active button
    navButtons.forEach((btn) => btn.classList.remove("active"))
    button.classList.add("active")

    // Show selected section
    const sectionId = button.dataset.section
    sections.forEach((section) => {
      section.classList.remove("section-active")
      if (section.id === sectionId) {
        section.classList.add("section-active")
      }
    })
  })
})

// Modal close buttons
closeButtons.forEach((button) => {
  button.addEventListener("click", () => {
    seatEditModal.style.display = "none"
    scheduleEditModal.style.display = "none"
    bookingEditModal.style.display = "none"
  })
})

// Close modal when clicking outside
window.addEventListener("click", (event) => {
  if (event.target === seatEditModal) {
    seatEditModal.style.display = "none"
  }
  if (event.target === scheduleEditModal) {
    scheduleEditModal.style.display = "none"
  }
  if (event.target === bookingEditModal) {
    bookingEditModal.style.display = "none"
  }
})

// Edit Seat
function editSeat(seatId) {
  // Get the seat element
  const seatElement = document.querySelector(`.seat[data-id="${seatId}"]`)

  // Set form values
  document.getElementById("edit-seat-id").value = seatId
  document.getElementById("seat-status").value = seatElement.classList.contains("booked")
    ? "booked"
    : seatElement.classList.contains("selected")
      ? "selected"
      : "available"

  // Show modal
  seatEditModal.style.display = "block"
}

// Edit Schedule
function editSchedule(scheduleId) {
  // Set form action
  document.getElementById("schedule-action").value = "update_schedule"
  document.getElementById("scheduleModalTitle").textContent = "Edit Schedule"

  // Get schedule data via AJAX
  fetch(`get-schedule.php?id=${scheduleId}`)
    .then((response) => response.json())
    .then((data) => {
      // Set form values
      document.getElementById("edit-schedule-id").value = data.id
      document.getElementById("schedule-route").value = data.route
      document.getElementById("schedule-departure").value = data.departure
      document.getElementById("schedule-arrival").value = data.arrival
      document.getElementById("schedule-seats").value = data.available_seats
      document.getElementById("schedule-status").value = data.status

      // Show modal
      scheduleEditModal.style.display = "block"
    })
    .catch((error) => {
      console.error("Error fetching schedule data:", error)
      alert("Error loading schedule data. Please try again.")
    })
}

// Add New Schedule
function showAddScheduleModal() {
  // Reset form
  document.getElementById("schedule-action").value = "add_schedule"
  document.getElementById("scheduleModalTitle").textContent = "Add New Schedule"
  document.getElementById("edit-schedule-id").value = ""
  document.getElementById("schedule-route").value = ""
  document.getElementById("schedule-departure").value = ""
  document.getElementById("schedule-arrival").value = ""
  document.getElementById("schedule-seats").value = ""
  document.getElementById("schedule-status").value = "on-time"

  // Show modal
  scheduleEditModal.style.display = "block"
}

// Edit Booking
function editBooking(bookingId) {
  // Set form action
  document.getElementById("booking-action").value = "update_booking"
  document.getElementById("bookingModalTitle").textContent = "Edit Booking"

  // Get booking data via AJAX
  fetch(`get-booking.php?id=${bookingId}`)
    .then((response) => response.json())
    .then((data) => {
      // Set form values
      document.getElementById("edit-booking-id").value = data.id
      document.getElementById("booking-passenger").value = data.passenger
      document.getElementById("booking-route").value = data.route
      document.getElementById("booking-date").value = data.date
      document.getElementById("booking-seat").value = data.seat
      document.getElementById("booking-status").value = data.status

      // Show modal
      bookingEditModal.style.display = "block"
    })
    .catch((error) => {
      console.error("Error fetching booking data:", error)
      alert("Error loading booking data. Please try again.")
    })
}

// Add New Booking
function showAddBookingModal() {
  // Reset form
  document.getElementById("booking-action").value = "add_booking"
  document.getElementById("bookingModalTitle").textContent = "Add New Booking"
  document.getElementById("edit-booking-id").value = ""
  document.getElementById("booking-passenger").value = ""
  document.getElementById("booking-route").value = ""
  document.getElementById("booking-date").value = ""
  document.getElementById("booking-seat").value = ""
  document.getElementById("booking-status").value = "confirmed"

  // Show modal
  bookingEditModal.style.display = "block"
}


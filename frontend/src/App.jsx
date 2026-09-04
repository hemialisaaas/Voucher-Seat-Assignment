import { useEffect, useState } from 'react'
import bgDark from './assets/dark.jpeg'
import bgLight from './assets/light.jpeg'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'
const THEME_STORAGE_KEY = 'voucher-app-theme'

// Reads a previously saved theme choice, falling back to the OS/browser
// preference, and finally to 'light' if neither is available.
function getInitialTheme() {
  try {
    const saved = localStorage.getItem(THEME_STORAGE_KEY)
    if (saved === 'light' || saved === 'dark') return saved
  } catch {
    // localStorage can throw in some privacy modes — ignore and fall through.
  }

  if (typeof window !== 'undefined' && window.matchMedia) {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  }

  return 'light'
}

const AIRCRAFT_TYPES = ['ATR', 'Airbus 320', 'Boeing 737 Max']

// Convert a DD-MM-YYYY string (as entered via <input type="date">, which
// natively gives YYYY-MM-DD) into the YYYY-MM-DD format the API expects.
// <input type="date"> already returns YYYY-MM-DD, so this is a pass-through
// kept explicit for clarity and in case the input format changes.
function toApiDateFormat(dateValue) {
  return dateValue
}

// Format YYYY-MM-DD as DD-MM-YYYY for display purposes, per the spec's
// front-end format note.
function toDisplayDateFormat(dateValue) {
  if (!dateValue) return ''
  const [year, month, day] = dateValue.split('-')
  return `${day}-${month}-${year}`
}

const initialFormState = {
  crewName: '',
  crewId: '',
  flightNumber: '',
  flightDate: '',
  aircraftType: '',
}

export default function App() {
  const [form, setForm] = useState(initialFormState)
  const [errors, setErrors] = useState({})
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [seats, setSeats] = useState(null)
  const [apiError, setApiError] = useState('')
  const [theme, setTheme] = useState(getInitialTheme)

  // Apply the theme to the document root (so CSS can key off
  // [data-theme="dark"|"light"]) and persist the choice.
  useEffect(() => {
    document.documentElement.setAttribute('data-theme', theme)
    try {
      localStorage.setItem(THEME_STORAGE_KEY, theme)
    } catch {
      // Ignore write failures (e.g. private browsing) — theme still
      // applies for the current page load via the attribute above.
    }
  }, [theme])

  function toggleTheme() {
    setTheme((current) => (current === 'dark' ? 'light' : 'dark'))
  }

  function handleChange(event) {
    const { name, value } = event.target
    setForm((prev) => ({ ...prev, [name]: value }))
  }

  function validate() {
    const nextErrors = {}
    if (!form.crewName.trim()) nextErrors.crewName = 'Crew name is required.'
    if (!form.crewId.trim()) nextErrors.crewId = 'Crew ID is required.'
    if (!form.flightNumber.trim()) nextErrors.flightNumber = 'Flight number is required.'
    if (!form.flightDate) nextErrors.flightDate = 'Flight date is required.'
    if (!form.aircraftType) nextErrors.aircraftType = 'Aircraft type is required.'
    return nextErrors
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setApiError('')
    setSeats(null)

    const validationErrors = validate()
    setErrors(validationErrors)
    if (Object.keys(validationErrors).length > 0) return

    setIsSubmitting(true)

    try {
      const checkResponse = await fetch(`${API_BASE_URL}/check`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          flightNumber: form.flightNumber,
          date: toApiDateFormat(form.flightDate),
        }),
      })

      if (!checkResponse.ok) {
        throw new Error('Unable to check existing vouchers. Please try again.')
      }

      const checkData = await checkResponse.json()

      if (checkData.exists) {
        setApiError(
          `Vouchers have already been generated for flight ${form.flightNumber} on ${toDisplayDateFormat(
            form.flightDate
          )}.`
        )
        return
      }

      const generateResponse = await fetch(`${API_BASE_URL}/generate`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: form.crewName,
          id: form.crewId,
          flightNumber: form.flightNumber,
          date: toApiDateFormat(form.flightDate),
          aircraft: form.aircraftType,
        }),
      })

      const generateData = await generateResponse.json()

      if (!generateResponse.ok) {
        throw new Error(generateData.message || 'Failed to generate vouchers.')
      }

      setSeats(generateData.seats)
    } catch (err) {
      setApiError(err.message || 'Something went wrong. Please try again.')
    } finally {
      setIsSubmitting(false)
    }
  }

  function handleReset() {
    setForm(initialFormState)
    setErrors({})
    setSeats(null)
    setApiError('')
  }

  return (
    <div className="page">
      <div className="sky" aria-hidden="true">
        <div className="sky-image sky-dark" style={{ backgroundImage: `url(${bgDark})` }} />
        <div className="sky-image sky-light" style={{ backgroundImage: `url(${bgLight})` }} />
      </div>

      <button
        type="button"
        className="theme-toggle"
        onClick={toggleTheme}
        aria-label={theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'}
        title={theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'}
      >
        {theme === 'dark' ? '☀️' : '🌙'}
      </button>

      <div className="card">
        <h1>Voucher Seat Assignment</h1>
        <p className="subtitle">Assign 3 random seats to voucher winners for a flight.</p>

        <form onSubmit={handleSubmit} noValidate>
          <div className="field">
            <label htmlFor="crewName">Crew Name</label>
            <input
              id="crewName"
              name="crewName"
              type="text"
              value={form.crewName}
              onChange={handleChange}
            />
            {errors.crewName && <span className="error">{errors.crewName}</span>}
          </div>

          <div className="field">
            <label htmlFor="crewId">Crew ID</label>
            <input
              id="crewId"
              name="crewId"
              type="text"
              value={form.crewId}
              onChange={handleChange}
            />
            {errors.crewId && <span className="error">{errors.crewId}</span>}
          </div>

          <div className="field">
            <label htmlFor="flightNumber">Flight Number</label>
            <input
              id="flightNumber"
              name="flightNumber"
              type="text"
              placeholder="e.g. GA102"
              value={form.flightNumber}
              onChange={handleChange}
            />
            {errors.flightNumber && <span className="error">{errors.flightNumber}</span>}
          </div>

          <div className="field">
            <label htmlFor="flightDate">Flight Date</label>
            <input
              id="flightDate"
              name="flightDate"
              type="date"
              value={form.flightDate}
              onChange={handleChange}
            />
            {errors.flightDate && <span className="error">{errors.flightDate}</span>}
          </div>

          <div className="field">
            <label htmlFor="aircraftType">Aircraft Type</label>
            <select
              id="aircraftType"
              name="aircraftType"
              value={form.aircraftType}
              onChange={handleChange}
            >
              <option value="">Select aircraft type</option>
              {AIRCRAFT_TYPES.map((type) => (
                <option key={type} value={type}>
                  {type}
                </option>
              ))}
            </select>
            {errors.aircraftType && <span className="error">{errors.aircraftType}</span>}
          </div>

          <div className="actions">
            <button type="submit" disabled={isSubmitting}>
              {isSubmitting ? 'Generating…' : 'Generate Vouchers'}
            </button>
            <button type="button" className="secondary" onClick={handleReset}>
              Reset
            </button>
          </div>
        </form>

        {apiError && <div className="alert alert-error">{apiError}</div>}

        {seats && (
          <div className="alert alert-success">
            <p>Vouchers generated successfully! Assigned seats:</p>
            <div className="seats">
              {seats.map((seat) => (
                <span className="seat" key={seat}>
                  {seat}
                </span>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  )
}

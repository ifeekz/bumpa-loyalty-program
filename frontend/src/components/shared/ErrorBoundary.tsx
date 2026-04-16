import { Component, type ReactNode } from 'react'
import { ErrorMessage } from './ErrorMessage'

interface State { hasError: boolean }

export class ErrorBoundary extends Component<{ children: ReactNode; fallback?: ReactNode }, State> {
  state: State = { hasError: false }

  static getDerivedStateFromError(): State {
    return { hasError: true }
  }

  render() {
    if (this.state.hasError) {
      return (
        this.props.fallback ?? (
          <div className="flex items-center justify-center min-h-[400px]">
            <ErrorMessage message="Something went wrong. Please refresh the page." />
          </div>
        )
      )
    }
    return this.props.children
  }
}

export interface ApiPagination {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface ApiResponse<T> {
  success: true;
  message: string;
  data: T;
  pagination?: ApiPagination;
}

export type ValidationErrors = Record<string, string[]>;

export type ApiErrorKind = 'http' | 'network' | 'timeout' | 'cancelled' | 'invalid-response';

export class ApiError extends Error {
  constructor(
    message: string,
    public readonly kind: ApiErrorKind,
    public readonly status: number | null = null,
    public readonly errors: ValidationErrors = {},
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

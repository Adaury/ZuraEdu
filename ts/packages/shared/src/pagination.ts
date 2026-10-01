export interface Page<T> {
  data: T[];
  meta: {
    page: number;
    perPage: number;
    total: number;
    lastPage: number;
  };
}

export const PER_PAGE_DEFAULT = 30;
export const PER_PAGE_MAX = 100;

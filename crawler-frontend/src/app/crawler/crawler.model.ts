export interface CrawlerInterface {
    id: string;

    url: string;
    urlHash?: string;

    text: string;

    depth?: number;

    ownerIds: string[];

    createdAt: Date;
    updatedAt: Date
}

export interface CrawlerInterfaceWithChildCountable extends CrawlerInterface {
    childrenCount: number;
}

export interface CrawlerInterfaceWithChildren extends CrawlerInterface {
    children: CrawlerInterface[];
}

export enum CrawlerDisplayState {
    DisplayLoading = 0,
    DisplayIndexes = 1,
    DisplayLinks = 2,
}

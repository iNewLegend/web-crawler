import { Component, Input } from '@angular/core';
import { CrawlerInterfaceWithChildCountable } from "../crawler.model";
import { ApiCrawlerService } from "../../../api/api.crawler.service";

@Component( {
    selector: 'crawler-display-index',
    templateUrl: './crawler-display-index.component.html',
    styleUrls: [ './crawler-display-index.component.scss' ]
} )

export class CrawlerDisplayIndexComponent {
    @Input() items: CrawlerInterfaceWithChildCountable[] = [];

    constructor( private crawler: ApiCrawlerService ) {
    }

    onClick( item: CrawlerInterfaceWithChildCountable ) {
        location.hash = item.id;
    }

    onDeleteClick( item: CrawlerInterfaceWithChildCountable ) {
        this.crawler.deleteCrawler( item.id ).subscribe( () => {
            // TODO - Lazy, no time.
            location.hash = '';
            location.reload();
        } );
    }

    onReloadClick( item: CrawlerInterfaceWithChildCountable ) {
        this.crawler.updateCrawler( item.id ).subscribe( () => {
            // TODO - Lazy, no time.
            location.hash = '';
            location.reload();
        } );
    }
}

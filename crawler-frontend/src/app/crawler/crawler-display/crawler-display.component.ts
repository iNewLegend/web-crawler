import { Component, Input } from '@angular/core';
import { CrawlerInterface } from "../crawler.model";

@Component( {
    selector: 'crawler-display',
    templateUrl: './crawler-display.component.html',
    styleUrls: [ './crawler-display.component.scss' ]
} )

export class CrawlerDisplayComponent {
    @Input() items: CrawlerInterface[] = [];

    onClick( item: CrawlerInterface ) {
        if ( ! item.urlHash ) {
            return;
        }

        location.hash = item.urlHash;
    }
}
